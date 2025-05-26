<!-- public/calendar_sync.php -->
<div class="calendar-sync-container">
    <h2>Calendar Integration</h2>
    <?php if ($hasToken): ?>
        <div class="sync-status connected">
            ✅ Connected to Google Calendar
            <button id="disconnect-calendar">Disconnect</button>
        </div>
        <div id="calendar-events"></div>
    <?php else: ?>
        <a href="<?= $calendarAuthUrl ?>" class="google-connect-btn">
            <img src="google-icon.png" alt="Google">
            Connect Google Calendar
        </a>
    <?php endif; ?>
</div>

<script>
document.getElementById('disconnect-calendar').addEventListener('click', async () => {
    await fetch('/api/calendar/disconnect');
    location.reload();
});

// Load calendar events
if (window.googleCalendarEnabled) {
    loadCalendarEvents();
    
    async function loadCalendarEvents() {
        const response = await fetch('/api/calendar/events');
        const events = await response.json();
        // Render events in calendar-events div
    }
}
</script>