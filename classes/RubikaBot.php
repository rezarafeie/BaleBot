<?php
// classes/RubikaBot.php
require_once __DIR__ . '/Logger.php';

class RubikaBot {
    private $token;
    private $apiUrl = "https://messengerg2c66.iranlms.ir/"; // Typical Rubika Bot API endpoint
    private $bot_id;

    public function __construct($token, $bot_id = 1) {
        $this->token = $token;
        $this->bot_id = $bot_id;
    }

    private function request($method, $input = []) {
        $data = [
            'method' => $method,
            'input' => $input,
            'client' => [
                'app_name' => 'Main',
                'app_version' => '3.2.1',
                'platform' => 'Web',
                'package' => 'ir.resaneh1.iptv'
            ]
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "auth: " . $this->token
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            error_log('RubikaBot Request Error: ' . $err);
            Logger::log('api', "RubikaBot Request Error: " . $err, ['method' => $method, 'input' => $input], $this->bot_id);
        } else {
            Logger::log('api', "RubikaBot API: " . $method, ['input' => $input, 'response' => json_decode($response, true) ?: $response], $this->bot_id);
        }
        curl_close($ch);
        
        return json_decode($response, true);
    }

    public function sendMessage($chat_id, $text, $reply_markup = null) {
        $input = [
            'object_guid' => $chat_id,
            'rnd' => rand(10000, 99999),
            'text' => $text
        ];
        if ($reply_markup) {
            // Rubika markup logic is quite complex, using simplified version if possible
            // Most Rubika Bot APIs use a different structure for buttons
            $input['reply_markup'] = $reply_markup;
        }
        return $this->request('sendMessage', $input);
    }
    
    public function sendPhoto($chat_id, $photo, $caption = null, $reply_markup = null) {
        // Rubika usually requires file upload first to get file_inline
        return $this->sendMessage($chat_id, $text . "\n" . "[Image placeholder]");
    }

    public function getChatMember($group_guid, $user_guid) {
        return $this->request('getGroupAdminMembers', ['group_guid' => $group_guid]);
    }

    public function getInlineKeyboard($buttons) {
        // Rubika inline keyboard structure
        $rows = [];
        foreach ($buttons as $row) {
            $newRow = [];
            foreach ($row as $btn) {
                $newRow[] = [
                    'text' => $btn['text'],
                    'type' => isset($btn['url']) ? 'Url' : 'Callback',
                    'url' => $btn['url'] ?? null,
                    'callback_data' => $btn['callback_data'] ?? null
                ];
            }
            $rows[] = ['buttons' => $newRow];
        }
        return ['inline_keyboard' => $rows];
    }
    
    public function getContactKeyboard($text) {
        return [
            'keyboard' => [[['text' => $text, 'button_type' => 'RequestPhone']]],
            'resize_keyboard' => true
        ];
    }
}
