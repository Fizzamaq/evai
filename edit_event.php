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
$eventTypes = $pdo->query("SELECT * FROM event_types")->fetchAll();
$eventServices = $pdo->prepare("SELECT service_id FROM event_service_requirements WHERE event_id = ?");
$eventServices->execute([$eventId]);
$selectedServices = $eventServices->fetchAll(PDO::FETCH_COLUMN);
$allServices = $pdo->query("SELECT * FROM vendor_services")->fetchAll();

$error = $_SESSION['event_error'] ?? null;
unset($_SESSION['event_error']);
?>
<div class="event-form-container">
    <h1>Edit Event: <?= htmlspecialchars($eventDetails['title']) ?></h1>
    
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form action="process_event_update.php" method="post">
        <input type="hidden" name="event_id" value="<?= $eventId ?>">
        
        <div class="form-group">
            <label>Event Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($eventDetails['title']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Event Type</label>
            <select name="event_type_id" required>
                <?php foreach ($eventTypes as $type): ?>
                <option value="<?= $type['id'] ?>" <?= $type['id'] == $eventDetails['event_type_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type['type_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="event_date" value="<?= $eventDetails['event_date'] ?>" required>
            </div>
            <div class="form-group">
                <label>End Date (optional)</label>
                <input type="date" name="end_date" value="<?= $eventDetails['end_date'] ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($eventDetails['description']) ?></textarea>
        </div>
        
        <h3>Required Services</h3>
        <div class="services-list">
            <?php foreach ($allServices as $service): ?>
            <div class="service-checkbox">
                <input type="checkbox" name="services[]" value="<?= $service['id'] ?>" 
                    id="service_<?= $service['id'] ?>" <?= in_array($service['id'], $selectedServices) ? 'checked' : '' ?>>
                <label for="service_<?= $service['id'] ?>"><?= htmlspecialchars($service['service_name']) ?></label>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button type="submit" class="btn">Update Event</button>
    </form>
</div>
<?php include 'footer.php'; ?>