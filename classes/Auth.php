<?php
// classes/Auth.php
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($username, $password, $email = '') {
        $stmt = $this->db->prepare("SELECT id FROM admins WHERE username = ? OR (email = ? AND email != '')");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'نام کاربری یا ایمیل قبلاً انتخاب شده است.'];
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO admins (username, password_hash, email) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $password_hash, $email])) {
            return ['success' => true, 'message' => 'ثبت‌نام با موفقیت انجام شد.'];
        }
        return ['success' => false, 'message' => 'خطایی در ثبت‌نام رخ داد.'];
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            return true;
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }

    public function logout() {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    public function updatePassword($new_password) {
        if (!$this->isLoggedIn()) return false;
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$hash, $_SESSION['admin_id']]);
    }
}
