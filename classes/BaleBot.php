<?php
// classes/BaleBot.php
require_once __DIR__ . '/Logger.php';

class BaleBot {
    private $token;
    private $apiUrl;
    private $bot_id;

    public function __construct($token = null, $bot_id = 1) {
        $this->token = $token ?: BOT_TOKEN;
        $this->apiUrl = "https://tapi.bale.ai/bot{$this->token}/";
        $this->bot_id = $bot_id;
    }

    private function request($method, $data = []) {
        $url = $this->apiUrl . $method;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Handle file uploads
        $hasFile = false;
        foreach ($data as $key => $value) {
            if ($value instanceof CURLFile || (is_string($value) && strpos($value, '@') === 0)) {
                $hasFile = true;
                break;
            }
        }

        if ($hasFile) {
            // For older CURL versions that used @
            // Using CURLFile is recommended
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            error_log('BaleBot Request Error: ' . $err);
            Logger::log('api', "BaleBot Request Error: " . $err, ['method' => $method, 'data' => $data], $this->bot_id);
        } else {
            Logger::log('api', "BaleBot API: " . $method, ['data' => $data, 'response' => json_decode($response, true) ?: $response], $this->bot_id);
        }
        curl_close($ch);
        
        return json_decode($response, true);
    }

    public function sendMessage($chat_id, $text, $reply_markup = null) {
        $data = ['chat_id' => (string)$chat_id, 'text' => $text];
        if ($reply_markup) $data['reply_markup'] = $reply_markup;
        return $this->request('sendMessage', $data);
    }

    public function editMessageText($chat_id, $message_id, $text, $reply_markup = null) {
        $data = [
            'chat_id' => (string)$chat_id,
            'message_id' => $message_id,
            'text' => $text
        ];
        if ($reply_markup) $data['reply_markup'] = $reply_markup;
        return $this->request('editMessageText', $data);
    }

    public function sendPhoto($chat_id, $photo, $caption = null, $reply_markup = null) {
        $data = ['chat_id' => (string)$chat_id, 'photo' => $photo];
        if ($caption !== null) $data['caption'] = $caption;
        if ($reply_markup) $data['reply_markup'] = $reply_markup;
        return $this->request('sendPhoto', $data);
    }

    public function sendDocument($chat_id, $document, $caption = null, $reply_markup = null) {
        $data = ['chat_id' => (string)$chat_id, 'document' => $document];
        if ($caption !== null) $data['caption'] = $caption;
        if ($reply_markup) $data['reply_markup'] = $reply_markup;
        return $this->request('sendDocument', $data);
    }

    public function sendVideo($chat_id, $video, $caption = null, $reply_markup = null) {
        $data = ['chat_id' => (string)$chat_id, 'video' => $video];
        if ($caption !== null) $data['caption'] = $caption;
        if ($reply_markup) $data['reply_markup'] = $reply_markup;
        return $this->request('sendVideo', $data);
    }

    public function sendVoice($chat_id, $voice, $caption = null, $reply_markup = null) {
        $data = ['chat_id' => (string)$chat_id, 'voice' => $voice];
        if ($caption !== null) $data['caption'] = $caption;
        if ($reply_markup) $data['reply_markup'] = $reply_markup;
        return $this->request('sendVoice', $data);
    }

    public function answerCallbackQuery($callback_query_id, $text = null, $show_alert = false) {
        $data = ['callback_query_id' => $callback_query_id];
        if ($text !== null) $data['text'] = $text;
        if ($show_alert) $data['show_alert'] = true;
        return $this->request('answerCallbackQuery', $data);
    }

    public function setWebhook($url) {
        return $this->request('setWebhook', ['url' => $url]);
    }

    public function deleteWebhook() {
        return $this->request('deleteWebhook');
    }

    public function getWebhookInfo() {
        return $this->request('getWebhookInfo');
    }

    public function getMe() {
        return $this->request('getMe');
    }

    public function getFile($file_id) {
        return $this->request('getFile', ['file_id' => $file_id]);
    }

    public function getChatMember($chat_id, $user_id) {
        return $this->request('getChatMember', [
            'chat_id' => (string)$chat_id,
            'user_id' => (int)$user_id
        ]);
    }

    // Helper to generate Contact Keyboard
    public function getContactKeyboard($text = "ارسال شماره تماس") {
        return [
            'keyboard' => [
                [
                    ['text' => $text, 'request_contact' => true]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];
    }

    // Helper to generate Inline Keyboard
    public function getInlineKeyboard($buttons) {
        return [
            'inline_keyboard' => $buttons
        ];
    }
}
