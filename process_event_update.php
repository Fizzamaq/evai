<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$eventId = (int)$_POST['event_id'];

$eventData = [
    'event_id' => $eventId,
    'title' => trim($_POST['title']),
    'event_type_id' => (int)$_POST['event_type_id'],
    'description' => trim($_POST['description']) ?? null,
    'event_date' => $_POST['event_date'],
    'end_date' => $_POST['end_date'] ?? null,
    'services' => $_POST['services'] ?? []
];

try {
    // First verify the user owns this event
    $event = new Event($pdo);
    $userEvents = $event->getEvents($userId, null, $eventId);
    
    if (empty($userEvents)) {
        throw new Exception("Event not found or access denied");
    }
    
    // Update event details
    $stmt = $pdo->prepare("UPDATE events SET
                          title = ?, event_type_id = ?, description = ?,
                          event_date = ?, end_date = ?
                          WHERE id = ? AND user_id = ?");
    $stmt->execute([
        $eventData['title'],
        $eventData['event_type_id'],
        $eventData['description'],
        $eventData['event_date'],
        $eventData['end_date'],
        $eventId,
        $userId
    ]);
    
    // Update services
    $pdo->prepare("DELETE FROM event_service_requirements WHERE event_id = ?")
        ->execute([$eventId]);
    
    foreach ($eventData['services'] as $serviceId) {
        $pdo->prepare("INSERT INTO event_service_requirements
                      (event_id, service_id, priority)
                      VALUES (?, ?, 'medium')")
            ->execute([$eventId, (int)$serviceId]);
    }
    
    $_SESSION['event_success'] = "Event updated successfully";
    header("Location: event.php?id=$eventId");
    exit();
    
} catch (Exception $e) {
    $_SESSION['event_error'] = $e->getMessage();
    header("Location: edit_event.php?id=$eventId");
    exit();
}