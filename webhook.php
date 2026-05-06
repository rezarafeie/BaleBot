<?php
// webhook.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/BaleBot.php';
require_once __DIR__ . '/classes/TelegramBot.php';
require_once __DIR__ . '/classes/RubikaBot.php';
require_once __DIR__ . '/classes/EventManager.php';
require_once __DIR__ . '/classes/RegistrationManager.php';
require_once __DIR__ . '/classes/BotManager.php';
require_once __DIR__ . '/classes/Logger.php';

// Determine Bot ID and Platform
$bot_id = $bot_id ?? ($_GET['bot_id'] ?? null);
$bot_user = $bot_user ?? ($_GET['bot_user'] ?? null);
$requested_platform = $_GET['platform'] ?? 'bale';

// Attempt to sync if connected
if (Database::getInstance()->isConnected()) {
    require_once __DIR__ . '/classes/SyncManager.php';
    SyncManager::processQueue();
}

$botManager = new BotManager();

$input = file_get_contents('php://input');
$update = json_decode($input, true);

// Primitive emergency logger to detect ANY hit
@file_put_contents(__DIR__ . '/data/webhook_hits.log', date('Y-m-d H:i:s') . " - Body: " . (trim($input) ? "Has content" : "Empty") . " - URI: " . $_SERVER['REQUEST_URI'] . " - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . PHP_EOL, FILE_APPEND);

if (trim($input)) {
    // Determine a temporary bot_id for logging if we don't have one yet
    $temp_bot_id = $_GET['bot_id'] ?? 1;
    Logger::log('webhook_raw', 'Raw hit', $update ?: ['raw' => $input], $temp_bot_id);
}

// Ensure bots directory exists for those who still want physical files
if (!is_dir(__DIR__ . '/bots')) {
    @mkdir(__DIR__ . '/bots', 0777, true);
}

if ($bot_id) {
    $botData = $botManager->getBot($bot_id);
} elseif ($bot_user) {
    $botData = $botManager->getBotByUsername($bot_user);
} else {
    $botData = $botManager->getBot(1); // Default for backward compatibility
}

if (!$botData) {
    // If it's a GET request (potentially a verification), maybe don't 404 immediately
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        exit("Webhook is active. Use /bots/username/webhook_platform.php for your bot.");
    }
    http_response_code(404);
    exit("Bot not found");
}

$bot_id = $botData['id']; 

// Security check (weak by default for compatibility as discussed)
if (isset($_GET['secret']) && $_GET['secret'] !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit("Forbidden");
}

require_once __DIR__ . '/classes/GapGPT.php';

if ($update && $botData) {
     Logger::log('webhook', 'Incoming webhook [' . $botData['name'] . ']', $update, $bot_id);
}

if (!$update) {
    http_response_code(200);
    exit;
}

// Instantiate the correct bot class based on platform
$platform = $requested_platform;
if ($platform === 'telegram') {
    $bot = new TelegramBot($botData['telegram_token'], $bot_id);
} elseif ($platform === 'rubika') {
    $bot = new RubikaBot($botData['rubika_token'], $bot_id);
} else {
    $bot = new BaleBot($botData['token'], $bot_id);
}

$eventManager = new EventManager();
$regManager = new RegistrationManager();

$dbInstance = Database::getInstance();
$db = $dbInstance->getConnection();

// Determine if we are in "local only" mode
$isOffline = !$dbInstance->isConnected();

// Load event cache
$eventCache = $eventManager->getCachedData($bot_id);

try {
    if ($platform === 'rubika') {
        // Rubika payload structure adaptation
        if (isset($update['data']['message'])) {
            handleMessage($update['data']['message']);
        }
    } else {
        // Bale/Telegram payload
        if (isset($update['message'])) {
            handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            handleCallbackQuery($update['callback_query']);
        }
    }
} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
}

http_response_code(200);

function sendMediaOrMessage($chat_id, $text, $media_id = null) {
    global $bot, $db;
    
    if (!$media_id || !$db) {
        return $bot->sendMessage($chat_id, $text);
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM media_files WHERE id = ?");
        $stmt->execute([$media_id]);
        $mediaFile = $stmt->fetch();
    } catch (Exception $e) {
        return $bot->sendMessage($chat_id, $text);
    }
    
    if (!$mediaFile) {
        return $bot->sendMessage($chat_id, $text);
    }
    
    $file = $mediaFile['bale_file_id'] ?: new CURLFile(dirname(__DIR__) . '/' . ltrim($mediaFile['file_path'], '/'));
    
    if ($mediaFile['file_type'] === 'photo') {
        return $bot->sendPhoto($chat_id, $file, $text);
    } elseif ($mediaFile['file_type'] === 'video') {
        return $bot->sendVideo($chat_id, $file, $text);
    } elseif ($mediaFile['file_type'] === 'document') {
        return $bot->sendDocument($chat_id, $file, $text);
    } elseif ($mediaFile['file_type'] === 'voice') {
        return $bot->sendVoice($chat_id, $file, $text);
    }
    
    return $bot->sendMessage($chat_id, $text);
}

function handleMessage($msg) {
    global $bot, $eventManager, $regManager, $db, $eventCache, $bot_id, $platform;

    $chat_id = $msg['chat']['id'] ?? null;
    if (!$chat_id) return;

    $bale_user_id = $msg['from']['id'] ?? null;
    $name = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? ''));
    $username = $msg['from']['username'] ?? null;
    
    // Log user
    $regManager->updateBotUser($chat_id, $bale_user_id, $name, $username, $bot_id, $platform);

    $stateInfo = $regManager->getUserState($chat_id, $bot_id);
    $status = $stateInfo['status'] ?? 'idle';
    $current_event_id = $stateInfo['current_event_id'] ?? null;
    $current_step = $stateInfo['current_step_index'] ?? 0;
    $answers = json_decode($stateInfo['answers_json'] ?? '{}', true) ?: [];

    // Handle /start or cancel
    $text = $msg['text'] ?? '';
    if (strpos($text, '/start ') === 0) {
        $param = trim(substr($text, 7));
        $regManager->clearState($chat_id, $bot_id);
        
        $foundEvent = null;
        if (isset($eventCache['slug_' . $param])) {
            $eId = $eventCache['slug_' . $param];
            $foundEvent = $eventCache[$eId] ?? null;
        } elseif (isset($eventCache[$param])) {
            $foundEvent = $eventCache[$param];
        } else {
            // Fallback to manual check
            foreach ($eventCache as $k => $e) {
                if (is_numeric($k) && ($e['slug'] === $param || (string)$e['id'] === $param)) {
                    $foundEvent = $e;
                    break;
                }
            }
        }

        if ($foundEvent) {
            startEvent($chat_id, $foundEvent);
        } else {
            sendEventSelection($chat_id);
        }
        return;
    }

    if ($text === '/start' || $text === 'انصراف' || $text === '/cancel') {
        $regManager->clearState($chat_id, $bot_id);
        sendEventSelection($chat_id);
        return;
    }

    if ($status === 'registering' && $current_event_id) {
        processRegistrationStep($chat_id, $msg, $current_event_id, $current_step, $answers);
    } else {
        sendEventSelection($chat_id);
    }
}

function handleCallbackQuery($cq) {
    global $bot, $eventManager, $regManager, $eventCache, $bot_id, $platform;

    $chat_id = $cq['message']['chat']['id'] ?? null;
    $data = $cq['data'] ?? '';
    if (!$chat_id) return;

    if (strpos($data, 'event_start:') === 0) {
        $event_id = str_replace('event_start:', '', $data);
        $event = $eventCache[$event_id] ?? $eventManager->getEvent($event_id);
        if ($event && $event['is_active']) {
            startEvent($chat_id, $event);
        } else {
            $bot->sendMessage($chat_id, "این رویداد در حال حاضر فعال نیست.");
        }
    } elseif (strpos($data, 'dd_ans:') === 0) {
        $parts = explode(':', $data, 4);
        if (count($parts) == 4) {
            $event_id = $parts[1];
            $step_index = $parts[2];
            $opt_idx = $parts[3];
            
            $stateInfo = $regManager->getUserState($chat_id, $bot_id);
            if ($stateInfo && ($stateInfo['status'] ?? '') === 'registering' && 
                ($stateInfo['current_event_id'] ?? null) == $event_id && 
                ($stateInfo['current_step_index'] ?? 0) == $step_index) {
                
                $event = $eventCache[$event_id] ?? $eventManager->getEvent($event_id);
                $fields = $event['fields'] ?? $eventManager->getEventFields($event_id, true);
                if (isset($fields[$step_index])) {
                    $options = json_decode($fields[$step_index]['options_json'], true);
                    if (isset($options[$opt_idx])) {
                        $mockMsg = [
                            'text' => $options[$opt_idx],
                            'chat' => ['id' => $chat_id],
                            'from' => $cq['from'] ?? []
                        ];
                $answers = json_decode($stateInfo['answers_json'] ?? '{}', true) ?: [];
                        processRegistrationStep($chat_id, $mockMsg, $event_id, $step_index, $answers);
                    }
                }
            }
        }
    } elseif (strpos($data, 'check_join:') === 0) {
        $parts = explode(':', $data);
        if (count($parts) == 3) {
            $event_id = $parts[1];
            $step_index = $parts[2];
            
            $stateInfo = $regManager->getUserState($chat_id, $bot_id);
            if ($stateInfo && ($stateInfo['status'] ?? '') === 'registering' && 
                ($stateInfo['current_event_id'] ?? null) == $event_id && 
                ($stateInfo['current_step_index'] ?? 0) == $step_index) {
                
                $event = $eventCache[$event_id] ?? $eventManager->getEvent($event_id);
                $fields = $event['fields'] ?? $eventManager->getEventFields($event_id, true);
                if (isset($fields[$step_index]) && $fields[$step_index]['type'] === 'channel_membership') {
                    $channel = json_decode($fields[$step_index]['options_json'], true)[0] ?? '';
                    $bale_user_id = $cq['from']['id'] ?? null;
                    
                    if ($bale_user_id && $channel) {
                        $res = $bot->getChatMember($channel, $bale_user_id);
                        $status = $res['result']['status'] ?? 'left';
                        $is_member = in_array($status, ['member', 'creator', 'administrator']);
                        
                        if ($is_member) {
                            // Save verification status
                            global $db;
                            if ($db) {
                                try {
                                    $stmt = $db->prepare("SELECT verified_channels FROM bot_users WHERE chat_id = ? AND bot_id = ?");
                                    $stmt->execute([$chat_id, $bot_id]);
                                    $verified = json_decode($stmt->fetchColumn() ?: '[]', true) ?: [];
                                    if (!in_array($channel, $verified)) {
                                        $verified[] = $channel;
                                        $stmt = $db->prepare("UPDATE bot_users SET verified_channels = ? WHERE chat_id = ? AND bot_id = ?");
                                        $stmt->execute([json_encode($verified, JSON_UNESCAPED_UNICODE), $chat_id, $bot_id]);
                                    }
                                } catch (Exception $e) {}
                            }

                            $bot->answerCallbackQuery($cq['id'], "عضویت تایید شد ✅");
                            $mockMsg = [
                                'text' => 'عضویت تایید شد',
                                'chat' => ['id' => $chat_id],
                                'from' => $cq['from'] ?? [],
                                'is_checked_join' => true // custom flag
                            ];
                            $answers = json_decode($stateInfo['answers_json'] ?? '{}', true) ?: [];
                            processRegistrationStep($chat_id, $mockMsg, $event_id, $step_index, $answers);
                        } else {
                            $warn = $fields[$step_index]['error_message'] ?: "شما هنوز عضو کانال نشده‌اید. لطفاً ابتدا عضو شوید و سپس دکمه بررسی را بزنید.";
                            $bot->answerCallbackQuery($cq['id'], $warn, true);
                        }
                    } else {
                        $bot->answerCallbackQuery($cq['id'], "خطا در دریافت اطلاعات کاربر یا کانال.", true);
                    }
                }
            }
        }
    }
    $bot->answerCallbackQuery($cq['id']);
}

function sendMedia($chat_id, $media_id, $caption = '', $extra = []) {
    global $bot, $db;
    if (!$media_id || !$db) return false;

    try {
        $stmt = $db->prepare("SELECT * FROM media_files WHERE id = ?");
        $stmt->execute([$media_id]);
        $media = $stmt->fetch();
    } catch (Exception $e) { return false; }

    if (!$media) return false;

    $file = $media['bale_file_id'] ?: new CURLFile(dirname(__DIR__) . '/' . ltrim($media['file_path'], '/'));
    
    switch ($media['file_type']) {
        case 'photo': return $bot->sendPhoto($chat_id, $file, $caption, $extra);
        case 'video': return $bot->sendVideo($chat_id, $file, $caption, $extra);
        case 'document': return $bot->sendDocument($chat_id, $file, $caption, $extra);
        case 'voice': return $bot->sendVoice($chat_id, $file, $caption, $extra);
    }
    return false;
}

function sendEventSelection($chat_id) {
    global $bot, $eventManager, $eventCache, $bot_id;
    
    $events = [];
    foreach ($eventCache as $k => $v) {
        if (is_numeric($k)) $events[] = $v;
    }
    
    if (count($events) === 0) {
        $bot->sendMessage($chat_id, "در حال حاضر هیچ رویداد فعالی وجود ندارد.");
        return;
    }

    if (count($events) === 1) {
        startEvent($chat_id, $events[0]);
        return;
    }

    $buttons = [];
    foreach ($events as $event) {
        $buttons[] = [
            ['text' => $event['title'], 'callback_data' => 'event_start:' . $event['id']]
        ];
    }
    
    global $db;
    $setting_text = "";
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'event_selection_text' AND bot_id = ?");
            $stmt->execute([$bot_id]);
            $setting_text = $stmt->fetchColumn();
        } catch (Exception $e) {}
    }
    
    $message = $setting_text ?: "سلام 👋\nبه سامانه ثبتنام خوش آمدید.\nبرای شروع، لطفاً رویداد موردنظر خود را انتخاب کنید.";
    
    $bot->sendMessage($chat_id, $message, $bot->getInlineKeyboard($buttons));
}

function startEvent($chat_id, $event) {
    global $bot, $regManager, $eventManager, $bot_id;

    // Check duplicate
    if ($regManager->checkDuplicate($chat_id, $event['id'], $event['duplicate_setting'], $bot_id)) {
        $msg = $event['duplicate_message'] ?: "شما قبلا در این رویداد ثبت‌نام کرده‌اید.";
        $bot->sendMessage($chat_id, $msg, ['remove_keyboard' => true]);
        return;
    }

    // Welcome message
    if (!empty(trim($event['welcome_message']))) {
        if (!empty($event['welcome_media_id'])) {
            sendMedia($chat_id, $event['welcome_media_id'], $event['welcome_message'], ['remove_keyboard' => true]);
        } else {
            $bot->sendMessage($chat_id, $event['welcome_message'], ['remove_keyboard' => true]);
        }
    } elseif (!empty($event['welcome_media_id'])) {
        sendMedia($chat_id, $event['welcome_media_id'], '', ['remove_keyboard' => true]);
    }

    $regManager->setState($chat_id, $event['id'], 0, json_encode([]), 'registering', $bot_id);
    
    // Trigger first step
    askStep($chat_id, $event['id'], 0, []);
}

function replacePlaceholders($text, $answers) {
    if (!$text || !is_array($answers)) return $text;
    
    // Sort keys by length descending to avoid replacing {phone_number} with {phone} results
    $keys = array_keys($answers);
    usort($keys, function($a, $b) {
        return strlen($b) - strlen($a);
    });

    foreach ($keys as $key) {
        $value = $answers[$key];
        $valStr = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        $text = str_replace('{' . $key . '}', $valStr, $text);
    }
    return $text;
}

function askStep($chat_id, $event_id, $step_index, $answers = []) {
    global $bot, $eventManager, $eventCache;
    $event = $eventCache[$event_id] ?? $eventManager->getEvent($event_id);
    $fields = $event['fields'] ?? $eventManager->getEventFields($event_id, true);
    
    if ($step_index >= count($fields)) {
        // Complete
        return;
    }

    $field = $fields[$step_index];
    
    // Handle Simple Message Type (Non-blocking)
    if ($field['type'] === 'message') {
        $msgText = $field['help_text'] ?: $field['label'];
        $msgText = replacePlaceholders($msgText, $answers);

        // AI Generation if active for this message block
        if (!empty($field['is_ai_generated'])) {
            global $db, $bot_id;
            $apiKey = "";
            $model = 'gemini-2.5-flash-lite';
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_api_key' AND bot_id = ?");
                    $stmt->execute([$bot_id]);
                    $apiKey = $stmt->fetchColumn();
                    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_model' AND bot_id = ?");
                    $stmt->execute([$bot_id]);
                    $model = $stmt->fetchColumn() ?: 'gemini-2.5-flash-lite';
                } catch (Exception $e) {}
            }

            if ($apiKey) {
                $aiResponse = GapGPT::call($msgText, $apiKey, $model);
                if ($aiResponse) $msgText = $aiResponse;
            }
        }

        if (!empty($field['media_id'])) {
            sendMedia($chat_id, $field['media_id'], $msgText, ['remove_keyboard' => true]);
        } else {
            $bot->sendMessage($chat_id, $msgText, ['remove_keyboard' => true]);
        }

        // Advance state silently
        $answers[$field['field_key']] = 'Displayed';
        $next_index = $step_index + 1;
        global $regManager, $bot_id;
        $regManager->setState($chat_id, $event_id, $next_index, json_encode($answers, JSON_UNESCAPED_UNICODE), 'registering', $bot_id);
        
        if ($next_index < count($fields)) {
            askStep($chat_id, $event_id, $next_index, $answers);
        } else {
            // It was the last step, but it was just a message. Trigger completion.
            $reg_data = ['text' => 'Message Displayed', 'is_skipped_message' => true];
            processRegistrationStep($chat_id, $reg_data, $event_id, $step_index, $answers);
        }
        return;
    }

    // Persistent Channel Verification Check
    if ($field['type'] === 'channel_membership') {
        $channel = json_decode($field['options_json'], true)[0] ?? '';
        if ($channel) {
            global $db;
            $verified = [];
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT verified_channels FROM bot_users WHERE chat_id = ? AND bot_id = ?");
                    $stmt->execute([$chat_id, $bot_id]);
                    $verified = json_decode($stmt->fetchColumn() ?: '[]', true) ?: [];
                } catch (Exception $e) {}
            }
            if (in_array($channel, $verified)) {
                // Already verified once, skip this step
                $answers[$field['field_key']] = 'Verified (Cached)';
                $next_index = $step_index + 1;
                global $regManager;
                $regManager->setState($chat_id, $event_id, $next_index, json_encode($answers, JSON_UNESCAPED_UNICODE), 'registering', $bot_id);
                // Call askStep for the next field
                // Use a mock processRegistrationStep logic or just call askStep
                if ($next_index < count($fields)) {
                    askStep($chat_id, $event_id, $next_index, $answers);
                } else {
                    // It was the last step
                    $reg_data = ['text' => 'Verified (Cached)', 'is_checked_join' => true];
                    processRegistrationStep($chat_id, $reg_data, $event_id, $step_index, $answers);
                }
                return;
            }
        }
    }

    $prompt = $field['help_text'] ?: "لطفا {$field['label']} را وارد کنید:";
    $prompt = replacePlaceholders($prompt, $answers);
    
    $markup = null;
    if ($field['type'] === 'phone' || $field['type'] === 'contact') {
        $markup = $bot->getContactKeyboard("ارسال شماره تماس");
    } elseif ($field['type'] === 'dropdown' && !empty($field['options_json'])) {
        $options = json_decode($field['options_json'], true);
        if ($options) {
            $buttons = [];
            foreach ($options as $idx => $opt) {
                $buttons[] = [['text' => $opt, 'callback_data' => "dd_ans:{$event_id}:{$step_index}:{$idx}"]];
            }
            $markup = $bot->getInlineKeyboard($buttons);
        } else {
            $markup = ['remove_keyboard' => true];
        }
    } elseif ($field['type'] === 'channel_membership' && !empty($field['options_json'])) {
        $channel = json_decode($field['options_json'], true)[0] ?? '';
        $joinLink = null;
        if (strpos($channel, '@') === 0) {
            if ($GLOBALS['platform'] === 'telegram') $joinLink = "https://t.me/" . substr($channel, 1);
            elseif ($GLOBALS['platform'] === 'rubika') $joinLink = "https://rubika.ir/" . substr($channel, 1);
            else $joinLink = "https://ble.ir/" . substr($channel, 1);
        }
        
        $buttons = [];
        if ($joinLink) {
            $buttons[] = [['text' => "ورود به کانال 📢", 'url' => $joinLink]];
        }
        $buttons[] = [['text' => "عضو شدم، بررسی کن ✅", 'callback_data' => "check_join:{$event_id}:{$step_index}"]];
        $markup = $bot->getInlineKeyboard($buttons);
    } else {
        $markup = ['remove_keyboard' => true];
    }

    if (!empty($field['media_id'])) {
        sendMedia($chat_id, $field['media_id'], $prompt, (array)$markup);
    } else {
        $bot->sendMessage($chat_id, $prompt, $markup);
    }
}

function processRegistrationStep($chat_id, $msg, $event_id, $step_index, &$answers) {
    global $bot, $eventManager, $regManager, $eventCache, $bot_id, $platform;
    $event = $eventCache[$event_id] ?? $eventManager->getEvent($event_id);
    $fields = $event['fields'] ?? $eventManager->getEventFields($event_id, true);
    
    if ($step_index >= count($fields)) return;
    $field = $fields[$step_index];

    $value = null;
    
    // Extract value based on expected type
    if ($field['type'] === 'contact' || $field['type'] === 'phone') {
        if (isset($msg['contact'])) {
            $value = $msg['contact']['phone_number'];
        } else if (isset($msg['text'])) {
            $value = $msg['text'];
        }
    } elseif ($field['type'] === 'photo' && isset($msg['photo'])) {
        $value = end($msg['photo'])['file_id']; // get best res
    } elseif ($field['type'] === 'document' && isset($msg['document'])) {
        $value = $msg['document']['file_id'];
    } elseif ($field['type'] === 'video' && isset($msg['video'])) {
        $value = $msg['video']['file_id'];
    } elseif ($field['type'] === 'voice' && isset($msg['voice'])) {
        $value = $msg['voice']['file_id'];
    } elseif ($field['type'] === 'channel_membership') {
        if (isset($msg['is_checked_join']) && $msg['is_checked_join'] === true) {
            $value = 'Joined';
        } else {
            // User sent text instead of using button, try to verify now
            $channel = json_decode($field['options_json'], true)[0] ?? '';
            $bale_user_id = $msg['from']['id'] ?? null;
            if ($bale_user_id && $channel) {
                $res = $bot->getChatMember($channel, $bale_user_id);
                $status = $res['result']['status'] ?? 'left';
                if (in_array($status, ['member', 'creator', 'administrator'])) {
                    $value = 'Joined';
                }
            }
        }
    } else {
        $value = $msg['text'] ?? null;
    }

    // Ignore validation for skipped message blocks
    if (isset($msg['is_skipped_message']) && $msg['is_skipped_message']) {
        $value = 'Displayed';
    }

    if ($field['is_required'] && empty($value)) {
        if ($field['type'] === 'channel_membership') {
            $err = $field['error_message'] ?: "شما هنوز عضو کانال نشده‌اید. لطفاً ابتدا از طریق دکمه زیر عضو شوید و سپس روی 'عضو شدم' بزنید.";
            askStep($chat_id, $event_id, $step_index, $answers); 
            return;
        }
        $err = $field['error_message'] ?: "مقدار وارد شده معتبر نیست. لطفاً دوباره ارسال کنید.";
        $bot->sendMessage($chat_id, $err);
        return; // wait for valid input
    }
    
    // Validation rules (simple examples)
    if ($value && $field['type'] === 'dropdown' && !empty($field['options_json'])) {
        $options = json_decode($field['options_json'], true) ?: [];
        if (!in_array($value, $options)) {
            $err = $field['error_message'] ?: "لطفاً یکی از گزینه‌ها را انتخاب کنید.";
            $bot->sendMessage($chat_id, $err);
            return;
        }
    }
    if ($value && $field['type'] === 'number' && !is_numeric($value)) {
        $err = $field['error_message'] ?: "لطفا فقط عدد وارد کنید.";
        $bot->sendMessage($chat_id, $err);
        return;
    }

    $answers[$field['field_key']] = $value;
    
    $step_index++;
    $regManager->setState($chat_id, $event_id, $step_index, json_encode($answers, JSON_UNESCAPED_UNICODE), 'registering', $bot_id);

    if ($step_index < count($fields)) {
        askStep($chat_id, $event_id, $step_index, $answers);
    } else {
        // Registration complete
        $regManager->completeRegistration($chat_id, $event_id, json_encode($answers, JSON_UNESCAPED_UNICODE), $bot_id, $platform);
        $regManager->clearState($chat_id, $bot_id);
        
        // Trigger completion actions if configured
        triggerCompletionAction($event, $answers, $chat_id);
        
        if ($event['use_ai']) {
            global $db;
            $apiKey = "";
            $model = 'gemini-2.5-flash-lite';
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_api_key' AND bot_id = ?");
                    $stmt->execute([$bot_id]);
                    $apiKey = $stmt->fetchColumn();
                    
                    // Get model setting
                    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_model' AND bot_id = ?");
                    $stmt->execute([$bot_id]);
                    $model = $stmt->fetchColumn() ?: 'gemini-2.5-flash-lite';
                } catch (Exception $e) {}
            }
            
            if ($apiKey) {

                $waitMsg = !empty(trim($event['ai_wait_message'])) ? $event['ai_wait_message'] : "درحال پردازش اطلاعات شما با هوش مصنوعی... ⏳";
                $waitMsg = replacePlaceholders($waitMsg, $answers);

                if (!empty($event['ai_wait_media_id'])) {
                    sendMedia($chat_id, $event['ai_wait_media_id'], $waitMsg, ['remove_keyboard' => true]);
                } else {
                    $bot->sendMessage($chat_id, $waitMsg, ['remove_keyboard' => true]);
                }
                
                $aiPrompt = replacePlaceholders($event['ai_prompt'] ?? '', $answers);
                $userDataStr = json_encode($answers, JSON_UNESCAPED_UNICODE);
                
                $fullPrompt = $aiPrompt . "\n\nاطلاعات کاربر:\n" . $userDataStr;
                
                // Initial response message for streaming
                $sentMsg = $bot->sendMessage($chat_id, "🧠 در حال تفکر...");
                $msg_id = $sentMsg['result']['message_id'] ?? null;

                $aiResponse = GapGPT::call($fullPrompt, $apiKey, $model, $bot, $chat_id, $msg_id);
                
                if (!$aiResponse && !$msg_id) {
                    $bot->sendMessage($chat_id, "متاسفانه در ارتباط با هوش مصنوعی خطایی رخ داد.", ['remove_keyboard' => true]);
                } elseif ($aiResponse && !$msg_id) {
                     $bot->sendMessage($chat_id, $aiResponse, ['remove_keyboard' => true]);
                }
            } else {
                $doneMsg = "ثبت‌نام شما با موفقیت انجام شد. (خطا: کلید API سرویس هوش مصنوعی تنظیم نشده است.)";
                $bot->sendMessage($chat_id, $doneMsg, ['remove_keyboard' => true]);
            }
        } else {
            $doneMsg = $event['completion_message'] ?: "ثبتنام شما با موفقیت انجام شد ✅\nممنون که اطلاعاتتان را ارسال کردید.";
            $doneMsg = replacePlaceholders($doneMsg, $answers);
            
            if (!empty($event['completion_media_id'])) {
                sendMedia($chat_id, $event['completion_media_id'], $doneMsg, ['remove_keyboard' => true]);
            } else {
                $bot->sendMessage($chat_id, $doneMsg, ['remove_keyboard' => true]);
            }
        }

        // Handle Seamless Transition to Next Event
        if (!empty($event['next_event_id'])) {
            $nextEvent = $eventCache[$event['next_event_id']] ?? $eventManager->getEvent($event['next_event_id']);
            if ($nextEvent && $nextEvent['is_active']) {
                // Give a tiny delay or just start
                startEvent($chat_id, $nextEvent);
            }
        }
    }
}


function triggerCompletionAction($event, $answers, $chat_id) {
    if (!isset($event['action_type']) || $event['action_type'] === 'none') {
        return;
    }
    
    global $db;
    // Add built-ins
    $answers['chat_id'] = $chat_id;
    $answers['event_id'] = $event['id'] ?? '';
    $answers['event_title'] = $event['title'] ?? '';
    
    // Fetch user info for more placeholders
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM bot_users WHERE chat_id = ?");
            $stmt->execute([$chat_id]);
            $user = $stmt->fetch();
            if ($user) {
                $answers['user_name'] = $user['name'] ?? '';
                $answers['user_username'] = $user['username'] ?? '';
                
                // Try to guess first/last name if not explicitly in answers
                if (!isset($answers['first_name']) && isset($user['name'])) {
                    $parts = explode(' ', $user['name']);
                    $answers['first_name'] = $parts[0];
                    $answers['last_name'] = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
                }
            }
        } catch (Exception $e) {}
    }
    
    if ($event['action_type'] === 'webhook' && !empty($event['action_webhook_url'])) {
        $url = trim($event['action_webhook_url']);
        
        if (!empty($event['action_webhook_body'])) {
            $payload = replacePlaceholders($event['action_webhook_body'], $answers);
        } else {
            $payload = json_encode($answers, JSON_UNESCAPED_UNICODE);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        Logger::log('webhook', 'Webhook Response', [
            'url' => $url,
            'payload' => json_decode($payload, true) ?: $payload,
            'response' => $res,
            'error' => $err
        ], $bot_id);
        
    } elseif ($event['action_type'] === 'http_request' && !empty($event['action_http_url'])) {
        $urlTemplate = trim($event['action_http_url']);
        
        // We need to replace placeholders selectively and urlencode the VALUES only
        $url = $urlTemplate;
        
        // Sort keys by length descending to avoid partial replacement issues
        $keys = array_keys($answers);
        usort($keys, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($keys as $key) {
            $value = $answers[$key];
            if (is_array($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            
            // Only encode if it's going into a URL
            $encodedValue = urlencode($value);
            $url = str_replace('{' . $key . '}', $encodedValue, $url);
        }
        
        // Final fallback: remove any unreplaced {tags} to prevent malformed URLs
        $url = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $url);
        // Also ensure no raw spaces in URL
        $url = str_replace(' ', '%20', $url);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Sometimes needed for some APIs in this env
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        Logger::log('api', 'HTTP Action Response', [
            'url' => $url,
            'response' => $res,
            'error' => $err
        ], $bot_id);
    }
}
