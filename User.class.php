<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($email, $password, $firstName, $lastName, $userTypeId) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $this->pdo->beginTransaction();
        try {
            // Insert user
            $stmt = $this->pdo->prepare("INSERT INTO users 
                                        (user_type_id, email, password_hash, first_name, last_name, is_active) 
                                        VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$userTypeId, $email, $hashedPassword, $firstName, $lastName]);
            $userId = $this->pdo->lastInsertId();
            
            // Create profile
            $this->initUserProfile($userId);
            
            $this->pdo->commit();
            return $userId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, password_hash, user_type_id, first_name 
                                        FROM users 
                                        WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type_id'];
                $_SESSION['user_name'] = $user['first_name'];
                $this->initUserProfile($user['id']);
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    private function initUserProfile($userId) {
        try {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO user_profiles (user_id) VALUES (?)");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Profile init error: " . $e->getMessage());
        }
    }

    public function getProfile($userId) {
        $stmt = $this->pdo->prepare("SELECT u.*, up.* 
                                    FROM users u
                                    LEFT JOIN user_profiles up ON u.id = up.user_id
                                    WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function updateProfile($userId, $data) {
        try {
            $this->pdo->beginTransaction();
            
            // Update users table
            $stmt = $this->pdo->prepare("UPDATE users SET 
                                        first_name = ?, last_name = ?
                                        WHERE id = ?");
            $stmt->execute([$data['first_name'], $data['last_name'], $userId]);
            
            // Update user_profiles table
            $stmt = $this->pdo->prepare("UPDATE user_profiles SET
                                        address = ?, city = ?, state = ?
                                        WHERE user_id = ?");
            $stmt->execute([
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $userId
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Profile update failed: " . $e->getMessage());
        }
    }
}