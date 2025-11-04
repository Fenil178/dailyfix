document.addEventListener('DOMContentLoaded', () => {

    // Use a single event listener on the document
    document.addEventListener('click', (e) => {
        
        // Check if the clicked element is a "Clear All" button
        if (e.target && e.target.matches('.clear-all-btn')) {
            e.preventDefault();
            e.stopPropagation(); // Stop the dropdown from closing immediately

            // Find the parent dropdown of the *specific button that was clicked*
            const notificationDropdown = e.target.closest('.notification-dropdown');
            if (!notificationDropdown) return;

            // Find all elements *relative* to that specific dropdown
            const notificationList = notificationDropdown.querySelector('.notification-list');
            
            // BUG FIX: Select '.no-notifications' which exists in the PHP, not '.notification-empty'
            const notificationEmpty = notificationDropdown.querySelector('.no-notifications');
            
            // Find the correct badge (desktop or mobile) to hide
            const badgeId = (notificationDropdown.id === 'notificationDropdown') 
                            ? '#unreadNotificationBadge' 
                            : '#unreadNotificationBadge-mobile';
            const notificationBadge = document.querySelector(badgeId);

            fetch('/dailyfix/api/clear_all_notifications.php', {
                method: 'POST',
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. Clear the list visually
                    if (notificationList) {
                        notificationList.innerHTML = '';
                    }

                    // 2. Show the "empty" message (using the correct, fixed selector)
                    if (notificationEmpty) {
                        notificationEmpty.style.display = 'block';
                    }
                    
                    // 3. Hide the associated badge
                    if (notificationBadge) {
                        notificationBadge.style.display = 'none';
                    }
                    
                    // 4. ALSO hide the *other* badge, since they are mirrors
                    const otherBadgeId = (badgeId === '#unreadNotificationBadge') 
                                         ? '#unreadNotificationBadge-mobile' 
                                         : '#unreadNotificationBadge';
                    const otherBadge = document.querySelector(otherBadgeId);
                    if (otherBadge) {
                        otherBadge.style.display = 'none';
                    }

                    // 5. Close the dropdown
                    if (notificationDropdown) {
                        notificationDropdown.classList.remove('active');
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Failed to clear notifications:', error);
                alert('A network error occurred. Please try again.');
            });
        }
    });
});