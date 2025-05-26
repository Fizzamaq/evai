document.addEventListener('DOMContentLoaded', function() {
    // Load dynamic content based on user type
    const userId = <?php echo $_SESSION['user_id']; ?>;
    const userType = <?php echo $user['user_type_id']; ?>;
    
    if (userType === 1) {
        // Load customer data
        loadUpcomingEvents(userId);
    } else {
        // Load vendor data
        loadVendorMetrics(userId);
        loadRecentMessages(userId);
    }
});

function loadUpcomingEvents(userId) {
    fetch(`/api/events/upcoming?user_id=${userId}`)
        .then(response => response.json())
        .then(events => {
            const container = document.getElementById('upcoming-events');
            
            if (events.length === 0) {
                container.innerHTML = '<p>No upcoming events found.</p>';
                return;
            }
            
            let html = '<ul class="event-list">';
            events.forEach(event => {
                html += `
                    <li>
                        <h3>${event.title}</h3>
                        <p>${formatDate(event.event_date)} at ${event.venue_name || 'Location TBD'}</p>
                        <a href="/event.php?id=${event.id}">View Details</a>
                    </li>
                `;
            });
            html += '</ul>';
            
            container.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading events:', error);
            document.getElementById('upcoming-events').innerHTML = 
                '<p class="error">Could not load events. Please try again later.</p>';
        });
}

function loadVendorMetrics(vendorId) {
    fetch(`/api/vendors/metrics?id=${vendorId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('upcoming-bookings').textContent = data.upcoming_bookings;
            document.getElementById('total-earnings').textContent = `$${data.total_earnings}`;
        })
        .catch(error => {
            console.error('Error loading vendor metrics:', error);
        });
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}