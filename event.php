<?php
require_once '../includes/config.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

$eventId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

$event = new Event($pdo);
$eventDetails = $event->getEvents($userId, null, $eventId);

if (empty($eventDetails)) {
    header("Location: events.php");
    exit();
}

$eventDetails = $eventDetails[0];
?>
<div class="event-details-container">
    <h1><?= htmlspecialchars($eventDetails['title']) ?></h1>
    
    <div class="event-meta">
        <p><strong>Type:</strong> <?= htmlspecialchars($eventDetails['type_name']) ?></p>
        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($eventDetails['event_date'])) ?></p>
        <?php if ($eventDetails['end_date']): ?>
        <p><strong>End Date:</strong> <?= date('F j, Y', strtotime($eventDetails['end_date'])) ?></p>
        <?php endif; ?>
        <p><strong>Status:</strong> <?= ucfirst($eventDetails['status']) ?></p>
    </div>
    
    <?php if ($eventDetails['description']): ?>
    <div class="event-description">
        <h3>Description</h3>
        <p><?= nl2br(htmlspecialchars($eventDetails['description'])) ?></p>
    </div>
    <?php endif; ?>
    
    <div class="event-actions">
        <a href="edit_event.php?id=<?= $eventId ?>" class="btn">Edit Event</a>
        <a href="ai_chat.php?event_id=<?= $eventId ?>" class="btn primary">Get AI Recommendations</a>
    </div>
</div>
<?php include 'footer.php'; ?>