<?php

class GapGPT {
    public static function call($prompt, $apiKey, $model = "gemini-2.5-flash-lite", $bot = null, $chat_id = null, $msg_id = null) {
        $ch = curl_init('https://api.gapgpt.app/v1/chat/completions');
        
        $payload = [
            "model" => $model,
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "stream" => ($bot && $chat_id && $msg_id)
        ];
        
        $fullContent = "";
        $lastUpdate = time();
        $updateInterval = 2;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        if ($bot && $chat_id && $msg_id) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$fullContent, &$lastUpdate, $updateInterval, $bot, $chat_id, $msg_id) {
                $lines = explode("\n", $data);
                foreach ($lines as $line) {
                    if (strpos($line, 'data: ') === 0) {
                        $json = substr($line, 6);
                        if ($json === '[DONE]') continue;
                        $decoded = json_decode($json, true);
                        $content = $decoded['choices'][0]['delta']['content'] ?? '';
                        if ($content) {
                            $fullContent .= $content;
                            
                            if (time() - $lastUpdate >= $updateInterval) {
                                $bot->editMessageText($chat_id, $msg_id, $fullContent . " ✍️");
                                $lastUpdate = time();
                            }
                        }
                    }
                }
                return strlen($data);
            });
        }
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($bot && $chat_id && $msg_id) {
            $bot->editMessageText($chat_id, $msg_id, $fullContent ?: "پاسخی دریافت نشد.");
            return $fullContent;
        }

        if ($err) return false;
        
        $decoded = json_decode($response, true);
        return $decoded['choices'][0]['message']['content'] ?? false;
    }
}
