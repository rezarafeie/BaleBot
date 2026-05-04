<?php
// classes/LocalStore.php

class LocalStore {
    private static $instance = null;
    private $dataDir;
    private $queueFile;

    private function __construct() {
        $this->dataDir = dirname(__DIR__) . '/data/local_store';
        if (!is_dir($this->dataDir)) {
            @mkdir($this->dataDir, 0777, true);
        }
        $this->queueFile = $this->dataDir . '/sync_queue.json';
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new LocalStore();
        }
        return self::$instance;
    }

    public function save($collection, $id, $data) {
        $dir = $this->dataDir . '/' . $collection;
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = $dir . '/' . $id . '.json';
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return true;
    }

    public function get($collection, $id) {
        $file = $this->dataDir . '/' . $collection . '/' . $id . '.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return null;
    }

    public function getAll($collection) {
        $dir = $this->dataDir . '/' . $collection;
        $results = [];
        if (is_dir($dir)) {
            $files = glob($dir . '/*.json');
            foreach ($files as $file) {
                $results[] = json_decode(file_get_contents($file), true);
            }
        }
        return $results;
    }

    public function queueSync($action, $collection, $data) {
        $queue = $this->getQueue();
        $queue[] = [
            'action' => $action,
            'collection' => $collection,
            'data' => $data,
            'timestamp' => time()
        ];
        file_put_contents($this->queueFile, json_encode($queue, JSON_UNESCAPED_UNICODE));
    }

    public function getQueue() {
        if (file_exists($this->queueFile)) {
            return json_decode(file_get_contents($this->queueFile), true) ?: [];
        }
        return [];
    }

    public function clearQueue() {
        if (file_exists($this->queueFile)) {
            @unlink($this->queueFile);
        }
    }
}
