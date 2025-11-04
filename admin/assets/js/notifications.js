document.addEventListener('DOMContentLoaded', () => {
    // --- SUPABASE SETUP ---
    // Ensure these are your correct Supabase credentials
    const SUPABASE_URL = 'https://whznrsvlicbdgjkrpzvz.supabase.co';
    const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Indoem5yc3ZsaWNiZGdqa3JwenZ6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTQ3Mjc3NDcsImV4cCI6MjA3MDMwMzc0N30.rcsYdfPXSksc8B1xQUyrMvqhtKk5LD6Im0LvYQn5Yhc';
    
    // Initialize the client
    const supabaseClient = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

    // --- DOM ELEMENT SELECTION ---
    // Get all necessary elements from the header
    const bell = document.getElementById('notification-bell');
    const panel = document.getElementById('notification-panel');
    const list = document.getElementById('notification-list');
    const countBadge = document.getElementById('notification-count');

    // Failsafe: If elements aren't found, stop the script
    if (!bell || !panel || !list || !countBadge) {
        console.error('Notification JS Error: One or more required HTML elements (bell, panel, list, count) not found.');
        return;
    }

    // --- CHECK ADMIN LOGIN ---
    // This variable 'currentAdminId' MUST be defined in your footer.php
    if (typeof currentAdminId === 'undefined' || currentAdminId === 0) {
        console.log('Admin not logged in. Notifications disabled.');
        bell.style.display = 'none'; // Optional: Hide bell if no admin is logged in
        return;
    }

    let unreadCount = 0; // Local state for unread count

    // --- 1. FUNCTION: Render notifications to the list ---
    function renderNotifications(notifications) {
        list.innerHTML = ''; // Clear the list first
        if (!notifications || notifications.length === 0) {
            list.innerHTML = '<li class="notification-empty">No new notifications.</li>';
            return;
        }

        notifications.forEach(notif => {
            const li = document.createElement('li');
            // Add 'read' class if the notification is already read
            if (notif.is_read) {
                li.classList.add('read');
            }
            // Create the link and inner HTML
            li.innerHTML = `
                <a href="${notif.link || '#'}" data-id="${notif.id}">
                    ${notif.message}
                    <span class="notification-time">${notif.time}</span>
                </a>
            `;
            list.appendChild(li);
        });
    }

    // --- 2. FUNCTION: Update the count badge ---
    function updateUnreadCount(count) {
        unreadCount = parseInt(count, 10); // Ensure it's a number
        if (unreadCount > 0) {
            countBadge.textContent = unreadCount;
            countBadge.classList.add('show');
        } else {
            countBadge.textContent = '0';
            countBadge.classList.remove('show');
        }
    }

    // --- 3. ASYNC FUNCTION: Fetch initial data on page load ---
    async function fetchInitialNotifications() {
        try {
            // Use the correct admin API path
            const response = await fetch('/dailyfix/admin/api/get_admin_notifications.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.notifications) {
                renderNotifications(data.notifications);
                updateUnreadCount(data.unread_count);
            } else {
                console.error('Error fetching admin notifications: Invalid data format.');
                list.innerHTML = '<li class="notification-empty">Error loading notifications.</li>';
            }
        } catch (error) {
            console.error('Error fetching admin notifications:', error);
            list.innerHTML = '<li class="notification-empty">Error loading notifications.</li>';
        }
    }

    // --- 4. FUNCTION: Listen for real-time inserts via Supabase ---
    function listenForNewNotifications() {
        supabaseClient
            .channel('public:admin_notifications') // Listen to the ADMIN table
            .on(
                'postgres_changes',
                { 
                    event: 'INSERT', 
                    schema: 'public', 
                    table: 'admin_notifications', // The table to monitor
                },
                (payload) => {
                    // Client-side check: Is this notification for THIS admin?
                    if (payload.new.admin_id === currentAdminId) {
                        console.log('New admin notification received:', payload.new);
                        
                        // Remove the "empty" message if it exists
                        const empty = list.querySelector('.notification-empty');
                        if(empty) {
                            empty.remove();
                        }

                        // Create the new notification list item
                        const li = document.createElement('li');
                        li.innerHTML = `
                            <a href="${payload.new.link || '#'}" data-id="${payload.new.id}">
                                ${payload.new.message}
                                <span class="notification-time">Just now</span>
                            </a>
                        `;
                        // Add it to the top of the list
                        list.prepend(li);
                        
                        // Increment and update the unread count badge
                        updateUnreadCount(unreadCount + 1);
                    }
                }
            )
            .subscribe((status) => {
                if (status === 'SUBSCRIBED') {
                    console.log('Supabase channel subscribed for admin notifications.');
                } else if (status === 'CHANNEL_ERROR' || status === 'TIMED_OUT') {
                    console.error('Supabase channel error for admin notifications.');
                }
            });
    }

    // --- 5. EVENT HANDLER: Bell icon click ---
    bell.addEventListener('click', async (e) => {
        e.stopPropagation(); // Prevent click from bubbling to document
        panel.classList.toggle('show');

        // If panel is opened AND there are unread notifications, mark them as read
        if (panel.classList.contains('show') && unreadCount > 0) {
            const currentUnread = unreadCount; // Store current count in case of error
            updateUnreadCount(0); // Optimistic UI update (set to 0 immediately)

            try {
                // Call the admin API to mark all as read
                const response = await fetch('/dailyfix/admin/api/mark_admin_notifications_read.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: '' // Sending empty body means "mark all"
                });

                if (!response.ok) {
                    throw new Error('Server error marking notifications as read.');
                }
                
                // On success, visually mark all items as read
                list.querySelectorAll('li:not(.read)').forEach(li => li.classList.add('read'));

            } catch (error) {
                console.error('Error marking admin notifications as read:', error);
                // Rollback: If API call fails, restore the previous unread count
                updateUnreadCount(currentUnread); 
            }
        }
    });

    // --- 6. EVENT HANDLER: Individual notification click ---
    list.addEventListener('click', async (e) => {
        const a = e.target.closest('a'); // Find the link that was clicked
        if (a) {
            e.preventDefault(); // Stop the browser from navigating immediately
            
            const id = a.dataset.id;
            const href = a.href;

            // Only make API call if the item is not already read
            if (!a.closest('li').classList.contains('read')) {
                try {
                    // Call the admin API to mark just this ONE as read
                    await fetch('/dailyfix/admin/api/mark_admin_notifications_read.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}` // Send the specific ID
                    });
                } catch (error) {
                    console.error('Error marking single admin notification as read:', error);
                }
            }

            // Navigate to the link's destination
            window.location.href = href;
        }
    });

    // --- 7. EVENT HANDLER: Click outside to close panel ---
    document.addEventListener('click', (e) => {
        // If the click is NOT on the panel AND NOT on the bell, close the panel
        if (!panel.contains(e.target) && !bell.contains(e.target)) {
            panel.classList.remove('show');
        }
    });

    // --- INITIALIZE ---
    // Start the process
    fetchInitialNotifications();
    listenForNewNotifications();
});