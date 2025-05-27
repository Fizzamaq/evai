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
            // Log the login attempt
            error_log("Attempting login for email: " . $email);

            $stmt = $this->pdo->prepare("SELECT id, password_hash, user_type_id, first_name 
                                        FROM users 
                                        WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

            if ($user) {
                // Log user data found
                error_log("User found: " . print_r($user, true));
                $password_match = password_verify($password, $user['password_hash']);
                error_log("Password verification result for " . $email . ": " . ($password_match ? 'true' : 'false'));

                if ($password_match) {
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true); 
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type_id'];
                    $_SESSION['user_name'] = $user['first_name'];
                    
                    // Ensure user_profiles is initialized for existing users too.
                    $this->initUserProfile($user['id']);
                    
                    error_log("Login successful for user ID: " . $user['id']);
                    return true;
                } else {
                    error_log("Password mismatch for email: " . $email);
                    return false;
                }
            } else {
                error_log("User not found or not active for email: " . $email);
                return false;
            }
        } catch (PDOException $e) {
            error_log("PDO Exception during login: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("General Exception during login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensures a user_profile entry exists for a given user_id.
     * Creates one if it doesn't exist.
     */
    private function initUserProfile($userId) {
        try {
            // Check if profile already exists to prevent duplicate inserts
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() == 0) {
                // If no profile exists, insert a new one
                $stmt = $this->pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            }
        } catch (PDOException $e) {
            error_log("Profile initialization error: " . $e->getMessage());
            // Do not re-throw, as this is a background initialization
        }
    }

    public function getProfile($userId) {
        // Select profile_image, address, city, state, country, postal_code from user_profiles table
        $stmt = $this->pdo->prepare("SELECT u.*, up.address, up.city, up.state, up.country, up.postal_code, up.profile_image 
                                    FROM users u
                                    LEFT JOIN user_profiles up ON u.id = up.user_id
                                    WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array
    }

    public function updateProfile($userId, $data) {
        try {
            $this->pdo->beginTransaction();

            // Update users table (for first/last name)
            $stmt = $this->pdo->prepare("UPDATE users SET 
                                        first_name = ?, last_name = ?, updated_at = NOW()
                                        WHERE id = ?");
            $stmt->execute([$data['first_name'], $data['last_name'], $userId]);

            // --- Update or Insert into user_profiles table ---
            // Check if a user_profile entry already exists for this user_id
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            $profileExists = ($stmt->fetchColumn() > 0);

            if ($profileExists) {
                // If profile exists, update it
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
                // If no profile exists, insert a new one (should ideally be prevented by initUserProfile)
                // This branch is a fallback and should ideally not be hit if initUserProfile works correctly on registration/login.
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

    // Get user by ID (including profile data)
    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT u.*, up.address, up.city, up.state, up.country, up.postal_code, up.profile_image FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Initiate password reset process
    public function initiatePasswordReset($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
                
                // Delete any old tokens for this user to prevent accumulation
                $deleteStmt = $this->pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $deleteStmt->execute([$user['id']]);

                $stmt = $this->pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $token, $expires]);

                // In a real application, you'd send an email here with the reset link
                error_log("Password reset link for $email: " . BASE_URL . "public/reset_password.php?token=$token");
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Initiate password reset error: " . $e->getMessage());
            return false;
        }
    }

    // Validate password reset token
    public function validateResetToken($token) {
        $stmt = $this->pdo->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Reset user's password
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

            // Invalidate the token
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

    // Checks if a user is an admin
    public function isAdmin($userId) {
        if ($userId === null) return false;
        $stmt = $this->pdo->prepare("SELECT user_type_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['user_type_id'] == 3; // Assuming 3 is admin type
    }

    // Checks if a user is a vendor
    public function isVendor($userId) {
        if ($userId === null) return false;
        $stmt = $this->pdo->prepare("SELECT user_type_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['user_type_id'] == 2; // Assuming 2 is vendor type
    }

    // Update profile image in user_profiles table
    public function updateProfileImage($userId, $filename) {
        // Modified to update profile_image in user_profiles table
        $stmt = $this->pdo->prepare("UPDATE user_profiles SET profile_image = ?, updated_at = NOW() WHERE user_id = ?");
        return $stmt->execute([$filename, $userId]);
    }
}
