<?php
// public/vendor_portfolio.php
session_start();
require_once '../includes/config.php';
require_once '../classes/User.class.php';
require_once '../classes/Vendor.class.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user = new User();
$vendor = new Vendor();
$user_data = $user->getUserById($_SESSION['user_id']);
$vendor_data = $vendor->getVendorByUserId($_SESSION['user_id']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_portfolio_item'])) {
        $upload_dir = '../assets/uploads/vendors/';
        $image_url = null;
        
        if (isset($_FILES['portfolio_image']) && $_FILES['portfolio_image']['error'] === UPLOAD_ERR_OK) {
            $file_ext = pathinfo($_FILES['portfolio_image']['name'], PATHINFO_EXTENSION);
            $file_name = 'portfolio_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
            move_uploaded_file($_FILES['portfolio_image']['tmp_name'], $upload_dir . $file_name);
            $image_url = 'assets/uploads/vendors/' . $file_name;
        }
        
        $portfolio_data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'event_type_id' => $_POST['event_type_id'] ?? null,
            'image_url' => $image_url,
            'project_date' => $_POST['project_date'] ?? null,
            'client_testimonial' => $_POST['testimonial'] ?? null,
            'is_featured' => isset($_POST['is_featured'])
        ];
        
        if ($vendor->addPortfolioItem($vendor_data['id'], $portfolio_data)) {
            $_SESSION['success_message'] = 'Portfolio item added successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to add portfolio item.';
        }
        header('Location: vendor_portfolio.php');
        exit();
    }
}

// Get vendor portfolio items
$portfolio_items = $vendor->getVendorPortfolio($vendor_data['id']);

// Get event types for dropdown
try {
    $stmt = $conn->query("SELECT id, type_name FROM event_types WHERE is_active = TRUE");
    $event_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $event_types = [];
    error_log("Get event types error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Portfolio - EventCraftAI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .portfolio-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .portfolio-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e1e5e9;
        }
        
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .portfolio-item {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .portfolio-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .portfolio-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            background-color: #f8f9fa;
        }
        
        .portfolio-content {
            padding: 20px;
        }
        
        .portfolio-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 10px;
        }
        
        .portfolio-meta {
            color: #636e72;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .portfolio-description {
            color: #636e72;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .portfolio-testimonial {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            font-style: italic;
            color: #636e72;
            margin-top: 10px;
        }
        
        .portfolio-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3436;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .featured-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #636e72;
        }
        
        .empty-state h3 {
            color: #2d3436;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="portfolio-container">
        <div class="portfolio-header">
            <div>
                <h1>My Portfolio</h1>
                <p>Showcase your best work to attract more clients</p>
            </div>
            <div>
                <a href="vendor_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        
        <?php if (empty($portfolio_items)): ?>
            <div class="empty-state">
                <h3>No Portfolio Items Yet</h3>
                <p>Add your first portfolio item to showcase your work</p>
            </div>
        <?php else: ?>
            <div class="portfolio-grid">
                <?php foreach ($portfolio_items as $item): ?>
                    <div class="portfolio-item">
                        <?php if ($item['image_url']): ?>
                            <div class="portfolio-image" style="background-image: url('<?php echo htmlspecialchars($item['image_url']); ?>')"></div>
                        <?php else: ?>
                            <div class="portfolio-image" style="background-image: url('../assets/images/default-portfolio.jpg')"></div>
                        <?php endif; ?>
                        
                        <div class="portfolio-content">
                            <div class="portfolio-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            
                            <div class="portfolio-meta">
                                <?php if ($item['event_type_name']): ?>
                                    <span>🏷️ <?php echo htmlspecialchars($item['event_type_name']); ?></span>
                                <?php endif; ?>
                                <?php if ($item['project_date']): ?>
                                    <span>📅 <?php echo date('M Y', strtotime($item['project_date'])); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="portfolio-description">
                                <?php echo htmlspecialchars(substr($item['description'] ?? 'No description available', 0, 120)); ?>
                                <?php if (strlen($item['description'] ?? '') > 120): ?>...<?php endif; ?>
                            </div>
                            
                            <?php if ($item['client_testimonial']): ?>
                                <div class="portfolio-testimonial">
                                    "<?php echo htmlspecialchars(substr($item['client_testimonial'], 0, 100)); ?>"
                                    <?php if (strlen($item['client_testimonial']) > 100): ?>...<?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="portfolio-form">
            <h2>Add New Portfolio Item</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_type_id">Event Type</label>
                        <select id="event_type_id" name="event_type_id">
                            <option value="">Select event type</option>
                            <?php foreach ($event_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="project_date">Project Date</label>
                        <input type="date" id="project_date" name="project_date">
                    </div>
                    
                    <div class="form-group">
                        <label for="portfolio_image">Image</label>
                        <input type="file" id="portfolio_image" name="portfolio_image" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="testimonial">Client Testimonial</label>
                    <textarea id="testimonial" name="testimonial" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <div class="featured-checkbox">
                        <input type="checkbox" id="is_featured" name="is_featured">
                        <label for="is_featured">Feature this item in my profile</label>
                    </div>
                </div>
                
                <button type="submit" name="add_portfolio_item" class="btn btn-primary">Add Portfolio Item</button>
            </form>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Simple form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(e) {
                const title = document.getElementById('title');
                
                if (!title.value.trim()) {
                    e.preventDefault();
                    alert('Title is required');
                    title.style.borderColor = '#e74c3c';
                    title.focus();
                }
            });
            
            // Preview image before upload
            const imageInput = document.getElementById('portfolio_image');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            // You could show a preview here if needed
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>