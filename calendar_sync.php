<div class="calendar-sync-container">
    <h2>Calendar Integration</h2>
    <?php
    // These variables would typically be set by a PHP controller that handles Google Calendar logic
    $hasToken = false; // Placeholder: Replace with actual logic to check if user has a token
    $calendarAuthUrl = '#'; // Placeholder: Replace with actual Google Auth URL

    // Example of how these might be set if this were part of a larger system:
    // require_once '../classes/CalendarManager.class.php';
    // $calendarManager = new CalendarManager($pdo);
    // $hasToken = $calendarManager->hasToken($_SESSION['user_id']); // Assuming a hasToken method
    // $calendarAuthUrl = $calendarManager->getAuthUrl();
    ?>
    <?php if ($hasToken): ?>
        <div class="sync-status connected">
            ✅ Connected to Google Calendar
            <button id="disconnect-calendar" class="btn btn-danger">Disconnect</button>
        </div>
        <div id="calendar-events"></div>
    <?php else: ?>
        <a href="<?= htmlspecialchars($calendarAuthUrl) ?>" class="google-connect-btn">
            <img src="<?= ASSETS_PATH ?>images/google-icon.png" alt="Google"> Connect Google Calendar
        </a>
    <?php endif; ?>
</div>

<script>
document.getElementById('disconnect-calendar')?.addEventListener('click', async () => {
    // Check if disconnect-calendar button exists before adding listener
    try {
        const response = await fetch('/api/calendar/disconnect', { method: 'POST' }); // Use POST for state-changing actions
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to disconnect calendar. Please try again.');
        }
    } catch (error) {
        console.error('Error disconnecting calendar:', error);
        alert('An error occurred while disconnecting the calendar.');
    }
});

// Load calendar events
// window.googleCalendarEnabled is not defined, assuming it's related to $hasToken
if (<?= $hasToken ? 'true' : 'false' ?>) { // Check hasToken directly
    loadCalendarEvents();

    async function loadCalendarEvents() {
        try {
            const response = await fetch('/api/calendar/events');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const events = await response.json();
            const calendarEventsDiv = document.getElementById('calendar-events');
            if (calendarEventsDiv) {
                if (events.length > 0) {
                    calendarEventsDiv.innerHTML = '<h3>Upcoming Calendar Events:</h3><ul>' +
                        events.map(event => `<li><strong>${event.summary}</strong>: ${new Date(event.start.dateTime).toLocaleString()} - ${new Date(event.end.dateTime).toLocaleString()}</li>`).join('') +
                        '</ul>';
                } else {
                    calendarEventsDiv.innerHTML = '<p>No upcoming events found on Google Calendar.</p>';
                }
            }
        } catch (error) {
            console.error('Error loading calendar events:', error);
            const calendarEventsDiv = document.getElementById('calendar-events');
            if (calendarEventsDiv) {
                calendarEventsDiv.innerHTML = '<p class="error-message">Failed to load calendar events. Please try again later.</p>';
            }
        }
    }
}
</script>
