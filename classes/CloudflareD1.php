<?php
// classes/CloudflareD1.php

class CloudflareD1 {
    private $accountId;
    private $databaseId;
    private $apiToken;
    private $lastError = null;

    public function __construct($accountId, $databaseId, $apiToken) {
        $this->accountId = $accountId;
        $this->databaseId = $databaseId;
        $this->apiToken = $apiToken;
    }

    private $lastInsertId = null;

    public function query($sql, $params = []) {
        // Simple translation for NOW() to SQLite/D1 syntax
        $sql = str_ireplace('NOW()', "datetime('now')", $sql);

        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/d1/database/{$this->databaseId}/query";
        
        $body = [
            'sql' => $sql,
            'params' => $params
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->apiToken}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $this->lastError = "Connection Error: " . $err;
            return false;
        }

        $result = json_decode($response, true);
        if ($httpCode !== 200 || !isset($result['success']) || !$result['success']) {
            $this->lastError = $result['errors'][0]['message'] ?? "Unknown Cloudflare Error (HTTP $httpCode)";
            return false;
        }

        $res = $result['result'][0] ?? null;
        if (isset($res['meta']['last_row_id'])) {
            $this->lastInsertId = $res['meta']['last_row_id'];
        }

        return $res;
    }

    public function fetchAll($sql, $params = []) {
        $res = $this->query($sql, $params);
        return $res['results'] ?? [];
    }

    public function fetch($sql, $params = []) {
        $res = $this->query($sql, $params);
        return $res['results'][0] ?? null;
    }

    public function fetchColumn($sql, $params = []) {
        $res = $this->fetch($sql, $params);
        if ($res) {
            return reset($res);
        }
        return null;
    }

    public function execute($sql, $params = []) {
        $res = $this->query($sql, $params);
        return $res !== false;
    }

    public function lastInsertId() {
        return $this->lastInsertId; 
    }

    public function getError() {
        return $this->lastError;
    }

    // Mock PDO Statement for compatibility
    public function prepare($sql) {
        return new D1Statement($this, $sql);
    }

    public function exec($sql) {
        return $this->execute($sql);
    }
}

class D1Statement {
    private $d1;
    private $sql;
    private $params = [];
    private $lastResult = null;

    public function __construct($d1, $sql) {
        $this->d1 = $d1;
        $this->sql = $sql;
    }

    public function execute($params = []) {
        if (empty($params)) $params = $this->params;
        // Fix for mixed params if needed, but Cloudflare expects array of values
        $this->lastResult = $this->d1->query($this->sql, $params);
        return $this->lastResult !== false;
    }

    public function fetchAll() {
        return $this->lastResult['results'] ?? [];
    }

    public function fetch() {
        return $this->lastResult['results'][0] ?? null;
    }

    public function fetchColumn() {
        $row = $this->fetch();
        return $row ? reset($row) : null;
    }
}
