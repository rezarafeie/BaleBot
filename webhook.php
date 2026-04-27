<?php
// webhook.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/BaleBot.php';
require_once __DIR__ . '/classes/EventManager.php';
require_once __DIR__ . '/classes/RegistrationManager.php';

// Security check
if (isset($_GET['secret']) && $_GET['secret'] !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit("Forbidden");
}

$input = file_get_contents('php://input');
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
    
    $bot->sendMessage($chat_id, "سلام 👋\nبه سامانه ثبتنام خوش آمدید.\nبرای شروع، لطفاً رویداد موردنظر خود را انتخاب کنید.", $bot->getInlineKeyboard($buttons));
}

function startEvent($chat_id, $event) {
    global $bot, $regManager, $eventManager;

    // Check duplicate
    if ($regManager->checkDuplicate($chat_id, $event['id'], $event['duplicate_setting'])) {
        $msg = $event['duplicate_message'] ?: "شما قبلا در این رویداد ثبت‌نام کرده‌اید.";
        $bot->sendMessage($chat_id, $msg, ['remove_keyboard' => true]);
        return;
    }

    $msg = $event['welcome_message'] ?: "سلام 👋\nبرای ثبتنام در «{$event['title']}» آماده‌ای؟\nلطفاً اطلاعات خواسته‌شده را مرحله‌به‌مرحله ارسال کن.";
    $bot->sendMessage($chat_id, $msg, ['remove_keyboard' => true]);

    $regManager->setState($chat_id, $event['id'], 0, json_encode([]), 'registering');
    
    // Trigger first step
    askStep($chat_id, $event['id'], 0);
}

function askStep($chat_id, $event_id, $step_index) {
    global $bot, $eventManager;
    $fields = $eventManager->getEventFields($event_id);
    
    if ($step_index >= count($fields)) {
        // Complete
        return;
    }

    $field = $fields[$step_index];
    $prompt = $field['help_text'] ?: "لطفا {$field['label']} را وارد کنید:";
    
    $markup = null;
    if ($field['type'] === 'phone' || $field['type'] === 'contact') {
        $markup = $bot->getContactKeyboard("ارسال شماره تماس");
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
    $fields = $eventManager->getEventFields($event_id);
    
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
    if ($value && $field['type'] === 'number' && !is_numeric($value)) {
        $err = $field['error_message'] ?: "لطفا فقط عدد وارد کنید.";
        $bot->sendMessage($chat_id, $err);
        return;
    }

    $answers[$field['field_key']] = $value;
    
    $step_index++;
    $regManager->setState($chat_id, $event_id, $step_index, json_encode($answers, JSON_UNESCAPED_UNICODE), 'registering');

    if ($step_index < count($fields)) {
        askStep($chat_id, $event_id, $step_index);
    } else {
        // Registration complete
        $regManager->completeRegistration($chat_id, $event_id, json_encode($answers, JSON_UNESCAPED_UNICODE));
        $regManager->clearState($chat_id);
        
        $event = $eventManager->getEvent($event_id);
        $doneMsg = $event['completion_message'] ?: "ثبتنام شما با موفقیت انجام شد ✅\nممنون که اطلاعاتتان را ارسال کردید.";
        $bot->sendMessage($chat_id, $doneMsg, ['remove_keyboard' => true]);
    }
}
