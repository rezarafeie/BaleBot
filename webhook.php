<?php
// webhook.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/BaleBot.php';
require_once __DIR__ . '/classes/EventManager.php';
require_once __DIR__ . '/classes/RegistrationManager.php';
require_once __DIR__ . '/classes/Logger.php';

// Security check
if (isset($_GET['secret']) && $_GET['secret'] !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit("Forbidden");
}

$input = file_get_contents('php://input');
if (trim($input)) {
    Logger::log('webhook', 'Incoming webhook', json_decode($input, true) ?: ['raw' => $input]);
}
$update = json_decode($input, true);

if (!$update) {
    http_response_code(200);
    exit;
}

$bot = new BaleBot();
$eventManager = new EventManager();
$regManager = new RegistrationManager();

$db = Database::getInstance()->getConnection();

try {
    if (isset($update['message'])) {
        handleMessage($update['message']);
    } elseif (isset($update['callback_query'])) {
        handleCallbackQuery($update['callback_query']);
    }
} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
}

http_response_code(200);

function handleMessage($msg) {
    global $bot, $eventManager, $regManager, $db;

    $chat_id = $msg['chat']['id'] ?? null;
    if (!$chat_id) return;

    $bale_user_id = $msg['from']['id'] ?? null;
    $name = trim(($msg['from']['first_name'] ?? '') . ' ' . ($msg['from']['last_name'] ?? ''));
    $username = $msg['from']['username'] ?? null;
    
    // Log user
    $regManager->updateBotUser($chat_id, $bale_user_id, $name, $username);

    $stateInfo = $regManager->getUserState($chat_id);
    $status = $stateInfo['status'] ?? 'idle';
    $current_event_id = $stateInfo['current_event_id'] ?? null;
    $current_step = $stateInfo['current_step_index'] ?? 0;
    $answers = json_decode($stateInfo['answers_json'] ?? '{}', true) ?: [];

    // Handle /start or cancel
    $text = $msg['text'] ?? '';
    if (strpos($text, '/start ') === 0) {
        $param = trim(substr($text, 7));
        $regManager->clearState($chat_id);
        
        $events = $eventManager->getAllEvents(true);
        $found = false;
        foreach ($events as $event) {
            if ($event['slug'] === $param || (string)$event['id'] === $param) {
                startEvent($chat_id, $event);
                $found = true;
                break;
            }
        }
        if (!$found) {
            sendEventSelection($chat_id);
        }
        return;
    }

    if ($text === '/start' || $text === 'انصراف' || $text === '/cancel') {
        $regManager->clearState($chat_id);
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
    global $bot, $eventManager, $regManager;

    $chat_id = $cq['message']['chat']['id'] ?? null;
    $data = $cq['data'] ?? '';
    if (!$chat_id) return;

    if (strpos($data, 'event_start:') === 0) {
        $event_id = str_replace('event_start:', '', $data);
        $event = $eventManager->getEvent($event_id);
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
            
            $stateInfo = $regManager->getUserState($chat_id);
            if ($stateInfo && ($stateInfo['status'] ?? '') === 'registering' && 
                ($stateInfo['current_event_id'] ?? null) == $event_id && 
                ($stateInfo['current_step_index'] ?? 0) == $step_index) {
                
                $fields = $eventManager->getEventFields($event_id, true);
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
    }
    $bot->answerCallbackQuery($cq['id']);
}

function sendEventSelection($chat_id) {
    global $bot, $eventManager;
    $events = $eventManager->getAllEvents(true);
    
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
    $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'event_selection_text'");
    $setting_text = $stmt->fetchColumn();
    
    $message = $setting_text ?: "سلام 👋\nبه سامانه ثبتنام خوش آمدید.\nبرای شروع، لطفاً رویداد موردنظر خود را انتخاب کنید.";
    
    $bot->sendMessage($chat_id, $message, $bot->getInlineKeyboard($buttons));
}

function startEvent($chat_id, $event) {
    global $bot, $regManager, $eventManager;

    // Check duplicate
    if ($regManager->checkDuplicate($chat_id, $event['id'], $event['duplicate_setting'])) {
        $msg = $event['duplicate_message'] ?: "شما قبلا در این رویداد ثبت‌نام کرده‌اید.";
        $bot->sendMessage($chat_id, $msg, ['remove_keyboard' => true]);
        return;
    }

    // Welcome message
    if (!empty(trim($event['welcome_message']))) {
        $bot->sendMessage($chat_id, $event['welcome_message'], ['remove_keyboard' => true]);
    }

    $regManager->setState($chat_id, $event['id'], 0, json_encode([]), 'registering');
    
    // Trigger first step
    askStep($chat_id, $event['id'], 0, []);
}

function replacePlaceholders($text, $answers) {
    if (!$text || !is_array($answers)) return $text;
    foreach ($answers as $key => $value) {
        if (is_scalar($value)) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
    }
    return $text;
}

function askStep($chat_id, $event_id, $step_index, $answers = []) {
    global $bot, $eventManager;
    $fields = $eventManager->getEventFields($event_id, true);
    
    if ($step_index >= count($fields)) {
        // Complete
        return;
    }

    $field = $fields[$step_index];
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
                // bale inline keyboard needs rows, we will put each option on its own row 
                // or two per row depending on length but let's do 1 per row for simplicity
                $buttons[] = [['text' => $opt, 'callback_data' => "dd_ans:{$event_id}:{$step_index}:{$idx}"]];
            }
            $markup = $bot->getInlineKeyboard($buttons);
        } else {
            $markup = ['remove_keyboard' => true];
        }
    } else {
        $markup = ['remove_keyboard' => true];
    }

    // Handle media if exists
    if ($field['media_path']) {
        $fullPath = dirname(__DIR__) . $field['media_path']; // Assuming local file
        // Here we can just use sendPhoto or similar based on extending logic
        // For simplicity, just sending text now, but media can be added.
    }

    $bot->sendMessage($chat_id, $prompt, $markup);
}

function processRegistrationStep($chat_id, $msg, $event_id, $step_index, &$answers) {
    global $bot, $eventManager, $regManager;
    $fields = $eventManager->getEventFields($event_id, true);
    
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
    } else {
        $value = $msg['text'] ?? null;
    }

    if ($field['is_required'] && empty($value)) {
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
    $regManager->setState($chat_id, $event_id, $step_index, json_encode($answers, JSON_UNESCAPED_UNICODE), 'registering');

    if ($step_index < count($fields)) {
        askStep($chat_id, $event_id, $step_index, $answers);
    } else {
        // Registration complete
        $regManager->completeRegistration($chat_id, $event_id, json_encode($answers, JSON_UNESCAPED_UNICODE));
        $regManager->clearState($chat_id);
        
        $event = $eventManager->getEvent($event_id);
        
        if ($event['use_ai']) {
            global $db;
            $stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'gapgpt_api_key'");
            $apiKey = $stmt->fetchColumn();
            
            if ($apiKey) {
                $waitMsg = !empty(trim($event['ai_wait_message'])) ? $event['ai_wait_message'] : "درحال پردازش اطلاعات شما با هوش مصنوعی... ⏳";
                $bot->sendMessage($chat_id, $waitMsg, ['remove_keyboard' => true]);
                
                $aiPrompt = replacePlaceholders($event['ai_prompt'] ?? '', $answers);
                $userDataStr = json_encode($answers, JSON_UNESCAPED_UNICODE);
                
                $fullPrompt = $aiPrompt . "\n\nاطلاعات کاربر:\n" . $userDataStr;
                
                $aiResponse = callGapGPT($fullPrompt, $apiKey);
                
                if ($aiResponse) {
                    $bot->sendMessage($chat_id, $aiResponse, ['remove_keyboard' => true]);
                } else {
                    $bot->sendMessage($chat_id, "متاسفانه در ارتباط با هوش مصنوعی خطایی رخ داد.", ['remove_keyboard' => true]);
                }
            } else {
                $doneMsg = "ثبت‌نام شما با موفقیت انجام شد. (خطا: کلید API سرویس هوش مصنوعی تنظیم نشده است.)";
                $bot->sendMessage($chat_id, $doneMsg, ['remove_keyboard' => true]);
            }
        } else {
            $doneMsg = $event['completion_message'] ?: "ثبتنام شما با موفقیت انجام شد ✅\nممنون که اطلاعاتتان را ارسال کردید.";
            $doneMsg = replacePlaceholders($doneMsg, $answers);
            $bot->sendMessage($chat_id, $doneMsg, ['remove_keyboard' => true]);
        }
    }
}

function callGapGPT($prompt, $apiKey) {
    $ch = curl_init('https://api.gapgpt.app/v1/chat/completions');
    $payload = json_encode([
        "model" => "gapgpt-qwen-3.6",
        "messages" => [
            ["role" => "user", "content" => $prompt]
        ]
    ]);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    return $decoded['choices'][0]['message']['content'] ?? false;
}
