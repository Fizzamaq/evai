<?php
session_start();
require_once '../includes/config.php';
require_once '../classes/Event.class.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_event.php');
    exit();
}

$event = new Event();
$user_id = $_SESSION['user_id'];

// Validate required fields
$required_fields = ['title', 'event_type', 'event_date'];
$errors = [];

foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
    }
}

// Validate date
if (!empty($_POST['event_date'])) {
    $event_date = $_POST['event_date'];
    if (strtotime($event_date) < strtotime('today')) {
        $errors[] = 'Event date cannot be in the past.';
    }
} else {
    $errors[] = 'Event date is required.';
}

// Validate budget if provided
if (!empty($_POST['budget']) && !is_numeric($_POST['budget'])) {
    $errors[] = 'Budget must be a valid number.';
}

// Validate guest count if provided
if (!empty($_POST['guest_count']) && (!is_numeric($_POST['guest_count']) || $_POST['guest_count'] < 1)) {
    $errors[] = 'Guest count must be a positive number.';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: create_event.php');
    exit();
}

// Prepare event data
$event_data = [
    'user_id' => $user_id,
    'title' => trim($_POST['title']),
    'description' => trim($_POST['description'] ?? ''),
    'event_type' => $_POST['event_type'],
    'event_date' => $_POST['event_date'],
    'event_time' => !empty($_POST['event_time']) ? $_POST['event_time'] : null,
    'duration' => !empty($_POST['duration']) ? (int)$_POST['duration'] : null,
    'location' => trim($_POST['location'] ?? ''),
    'guest_count' => !empty($_POST['guest_count']) ? (int)$_POST['guest_count'] : null,
    'budget' => !empty($_POST['budget']) ? (float)$_POST['budget'] : null,
    'status' => $_POST['status'] ?? 'planning',
    'special_requirements' => trim($_POST['special_requirements'] ?? ''),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

// Handle services (convert array to JSON)
$services = [];
if (!empty($_POST['services']) && is_array($_POST['services'])) {
    $services = $_POST['services'];
}
$event_data['services_needed'] = json_encode($services);

try {
    // Create the event
    $event_id = $event->createEvent($event_data);
    
    if ($event_id) {
        // Success - redirect to event details or events list
        $_SESSION['success_message'] = 'Event created successfully!';
        header('Location: event.php?id=' . $event_id);
    } else {
        // Failed to create event
        $_SESSION['error_message'] = 'Failed to create event. Please try again.';
        $_SESSION['form_data'] = $_POST;
        header('Location: create_event.php');
    }
    
} catch (Exception $e) {
    // Handle database errors
    error_log('Event creation error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while creating the event. Please try again.';
    $_SESSION['form_data'] = $_POST;
    header('Location: create_event.php');
}

exit();
?>