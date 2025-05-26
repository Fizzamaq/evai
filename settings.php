<?php
session_start();
require_once '../../includes/config.php';
require_once '../../classes/User.class.php';
require_once '../../classes/SystemSettings.class.php';

$user = new User();
$settings = new SystemSettings();

if (!$user->isAdmin($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $settings->updateSetting($key, $value);
    }
    $_SESSION['success'] = "Settings updated successfully!";
    header('Location: settings.php');
    exit();
}

// Get all system settings
$system_settings = $settings->getAllSettings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - EventCraftAI</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .setting-item {
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .setting-key {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2d3436;
        }
        
        .setting-description {
            color: #636e72;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .setting-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="settings-container">
        <h1>System Settings</h1>
        
        <form method="POST">
            <?php foreach ($system_settings as $setting): ?>
                <div class="setting-item">
                    <div class="setting-key"><?php echo htmlspecialchars($setting['setting_key']); ?></div>
                    <div class="setting-description"><?php echo htmlspecialchars($setting['description']); ?></div>
                    
                    <?php if ($setting['data_type'] === 'boolean'): ?>
                        <label>
                            <input type="checkbox" name="settings[<?php echo $setting['setting_key']); ?>]" 
                                value="1" <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                            Enable
                        </label>
                    <?php elseif ($setting['data_type'] === 'json'): ?>
                        <textarea class="setting-input" name="settings[<?php echo $setting['setting_key']); ?>]" 
                            rows="4"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                    <?php else: ?>
                        <input type="text" class="setting-input" 
                            name="settings[<?php echo $setting['setting_key']); ?>]" 
                            value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary">Save All Changes</button>
        </form>
    </div>
</body>
</html>