<?php
// classes/MediaManager.php
require_once __DIR__ . '/Database.php';

class MediaManager {
    private $db;
    private $uploadDir;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->uploadDir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function getAllMedia() {
        return $this->db->query("SELECT * FROM media_files ORDER BY id DESC")->fetchAll();
    }

    public function uploadFile($file, $title) {
        $allowedTypes = ['image/jpeg', 'image/png', 'video/mp4', 'application/pdf', 'audio/mpeg', 'audio/ogg'];
        $typeMapping = [
            'image/jpeg' => 'photo',
            'image/png'  => 'photo',
            'video/mp4'  => 'video',
            'application/pdf' => 'document',
            'audio/mpeg' => 'audio',
            'audio/ogg'  => 'voice'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type.");
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $dest = $this->uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $file_type = $typeMapping[$file['type']] ?? 'document';
            $path = '/uploads/' . $filename;

            $stmt = $this->db->prepare("INSERT INTO media_files (file_path, file_type, file_size, title, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$path, $file_type, $file['size'], $title]);
            return $this->db->lastInsertId();
        }
        throw new Exception("Upload failed.");
    }
    
    public function deleteMedia($id) {
        $stmt = $this->db->prepare("SELECT file_path FROM media_files WHERE id = ?");
        $stmt->execute([$id]);
        $media = $stmt->fetch();
        if ($media) {
            $fullPath = dirname(__DIR__) . $media['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $this->db->prepare("DELETE FROM media_files WHERE id = ?")->execute([$id]);
        }
    }
}
