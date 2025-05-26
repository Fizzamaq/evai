<?php
require_once '../includes/config.php';
require_once '../includes/ai_functions.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ai = new AI_Assistant($pdo);
$suggestions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suggestions = $ai->generateEventSuggestions([
        'type' => $_POST['event_type'],
        'budget' => $_POST['budget'],
        'guests' => $_POST['guest_count']
    ]);
}
?>
<div class="ai-chat-container">
    <h2>AI Event Planning Assistant</h2>
    
    <form method="post" class="ai-chat-form">
        <div class="form-group">
            <label>Event Type</label>
            <select name="event_type" required>
                <option value="wedding">Wedding</option>
                <option value="corporate">Corporate Event</option>
                <option value="birthday">Birthday Party</option>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Guest Count</label>
                <input type="number" name="guest_count" min="1" required>
            </div>
            <div class="form-group">
                <label>Budget ($)</label>
                <input type="number" name="budget" min="100" required>
            </div>
        </div>
        
        <button type="submit" class="btn">Get Recommendations</button>
    </form>
    
    <?php if (!empty($suggestions)): ?>
    <div class="ai-results">
        <h3>AI Suggestions</h3>
        <div class="vendor-cards">
            <?php foreach (json_decode($suggestions, true) as $vendor): ?>
            <div class="vendor-card">
                <h4><?= htmlspecialchars($vendor['name']) ?></h4>
                <p>Service: <?= htmlspecialchars($vendor['service']) ?></p>
                <p>Estimated Cost: $<?= number_format($vendor['cost']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>