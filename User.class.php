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
            // Check for duplicate email error
            if ($e->getCode() == 23000) { // SQLSTATE for integrity constraint violation
                throw new Exception("Registration failed: Email already exists.");
            }
            throw new Exception("Registration failed: " . $e->getMessage());
        }
    }

    public function login($email, $password) {
        try {
            error_log("Attempting login for email: " . $email);

            $stmt = $this->pdo->prepare("SELECT id, password_hash, user_type_id, first_name
                                        FROM users
                                        WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                error_log("User found: " . print_r($user, true));
                $password_match = password_verify($password, $user['password_hash']);
                error_log("Password verification result for " . $email . ": " . ($password_match ? 'true' : 'false'));

                if ($password_match) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type_id'];
                    $_SESSION['user_name'] = $user['first_name'];

                    // Ensure user_profiles is initialized for existing users too.
                    $this->initUserProfile($user['id']);

                    error_log("Login successful for user ID: " . $user['id']);
                    return true;
                } else {
                    // Set a specific error message for invalid credentials
                    $_SESSION['login_error'] = "Invalid email or password.";
                    error_log("Password mismatch for email: " . $email);
                    return false;
                }
            } else {
                // Set a specific error message for invalid credentials
                $_SESSION['login_error'] = "Invalid email or password.";
                error_log("User not found or not active for email: " . $email);
                return false;
            }
        } catch (PDOException $e) {
            // Log and set a specific error message for database issues
            $_SESSION['login_error'] = "A database error occurred during login. Please try again later.";
            error_log("PDO Exception during login: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            // Log and set a specific error message for other exceptions
            $_SESSION['login_error'] = "An unexpected error occurred during login. Please try again.";
            error_log("General Exception during login: " . $e->getMessage());
            return false;
        }
    }

    private function initUserProfile($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $this->pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            }
        } catch (PDOException $e) {
            error_log("Profile initialization error: " . $e->getMessage());
            // It's crucial not to halt login here, but log the issue.
            // If profile initialization fails, subsequent profile-dependent features might break.
            // You might want to add a flag to the session indicating incomplete profile setup
            // or redirect to a profile completion page after login.
        }
    }

    public function getProfile($userId) {
        $stmt = $this->pdo->prepare("SELECT u.*, up.address, up.city, up.state, up.country, up.postal_code, up.profile_image
                                    FROM users u
                                    LEFT JOIN user_profiles up ON u.id = up.user_id
                                    WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($userId, $data) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE users SET
                                        first_name = ?, last_name = ?, updated_at = NOW()
                                        WHERE id = ?");
            $stmt->execute([$data['first_name'], $data['last_name'], $userId]);

            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profileExists = ($stmt->fetchColumn() > 0);

            if ($profileExists) {
                $stmt = $this->pdo->prepare("UPDATE user_profiles SET
                                            address = ?, city = ?, state = ?, country = ?, postal_code = ?, updated_at = NOW()
                                            WHERE user_id = ?");
                $stmt->execute([
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['country'] ?? null,
                    $data['postal_code'] ?? null,
                    $userId
                ]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO user_profiles (user_id, address, city, state, country, postal_code)
                                            VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['country'] ?? null,
                    $data['postal_code'] ?? null
                ]);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("User profile update failed: " . $e->getMessage());
            throw new Exception("User profile update failed: " . $e->getMessage());
        }
    }

    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT u.*, up.address, up.city, up.state, up.country, up.postal_code, up.profile_image FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function initiatePasswordReset($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $deleteStmt = $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $deleteStmt->execute([$user['id']]);

                $stmt = $this->pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires]);

                error_log("Password reset link for $email: " . BASE_URL . "public/reset_password.php?token=$token");
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Initiate password reset error: " . $e->getMessage());
            return false;
        }
    }

    public function validateResetToken($token) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function resetPassword($token, $newPassword) {
        try {
            $this->pdo->beginTransaction();
            $tokenData = $this->validateResetToken($token);

            if (!$tokenData) {
                throw new Exception("Invalid or expired token.");
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $tokenData['user_id']]);

            $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Reset password error: " . $e->getMessage());
            return false;
        }
    }

    public function isAdmin($userId) {
        if ($userId === null) return false;
        $stmt = $this->pdo->prepare("SELECT user_type_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['user_type_id'] == 3;
    }

    public function isVendor($userId) {
        if ($userId === null) return false;
        $stmt = $this->pdo->prepare("SELECT user_type_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['user_type_id'] == 2;
    }

    public function updateProfileImage($userId, $filename) {
        $stmt = $this->pdo->prepare("UPDATE user_profiles SET profile_image = ?, updated_at = NOW() WHERE user_id = ?");
        return $stmt->execute([$filename, $userId]);
    }
}
