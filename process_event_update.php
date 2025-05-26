<?php
require_once '../includes/config.php';
require_once '../classes/Event.class.php'; // Include Event class

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "public/login.php");
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
    $event = new Event($pdo); // Pass PDO
    $userEvents = $event->getEventById($eventId, $userId); // Use getEventById
    if (empty($userEvents)) {
        throw new Exception("Event not found or access denied");
    }

    // Update event details using Event class method or direct PDO
    // Using Event class method for consistency:
    $event->updateEvent($eventId, [
        'title' => $eventData['title'],
        'description' => $eventData['description'],
        'event_type' => $eventData['event_type_id'], // Assuming event_type accepts ID
        'event_date' => $eventData['event_date'],
        'event_time' => null, // This is not passed from the form, might need to be added
        'duration' => null, // Not passed
        'location' => null, // Not passed
        'guest_count' => null, // Not passed
        'budget' => null, // Not passed
        'status' => 'planning', // Default status, form doesn't provide
        'services_needed' => json_encode($eventData['services']), // Convert to JSON
        'special_requirements' => null, // Not passed
        'updated_at' => date('Y-m-d H:i:s')
    ], $userId);


    // Update services (needs to be handled outside Event class if Event class doesn't manage service requirements directly)
    dbQuery("DELETE FROM event_service_requirements WHERE event_id = ?", [$eventId]);

    foreach ($eventData['services'] as $serviceId) {
        dbQuery("INSERT INTO event_service_requirements
                      (event_id, service_id, priority)
                      VALUES (?, ?, 'medium')", [(int)$eventId, (int)$serviceId]);
    }

    $_SESSION['event_success'] = "Event updated successfully";
    header("Location: " . BASE_URL . "public/event.php?id=" . $eventId);
    exit();

} catch (Exception $e) {
    $_SESSION['event_error'] = $e->getMessage();
    header("Location: " . BASE_URL . "public/edit_event.php?id=" . $eventId);
    exit();
}
?>
