// /admin/assets/js/admin_app.js

document.addEventListener('DOMContentLoaded', () => {

    /**
     * Initializes all admin panel functionalities.
     */
    function init() {
        loadInitialTheme(); // Applies saved theme on page load
        initMobileMenu();
        initThemeToggle();
        initDropdownMenu();
        initLogoutModal();
        initDashboardAnimations();
        initPageLoad();
        initConfirmationModal();
        initBookingDetailsModal();
    }

    /**
     * Toggles the mobile navigation menu.
     */
    function initMobileMenu() {
        const menuButton = document.getElementById('mobile-menu');
        const navLinks = document.getElementById('navLinks');
        if (menuButton && navLinks) {
            menuButton.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
    }

    /**
     * Toggles between light and dark themes and saves the choice.
     */
    function initThemeToggle() {
        const themeToggleButton = document.getElementById('theme-toggle-btn');
        const icon = themeToggleButton ? themeToggleButton.querySelector('i') : null;

        if (themeToggleButton && icon) {
            themeToggleButton.addEventListener('click', () => {
                // 1. Toggle the class on the body
                document.body.classList.toggle('dark-mode');

                // 2. Determine the new theme and save it
                let newTheme;
                if (document.body.classList.contains('dark-mode')) {
                    newTheme = 'dark';
                    localStorage.setItem('theme', 'dark');
                } else {
                    newTheme = 'light';
                    localStorage.setItem('theme', 'light');
                }
                
                // 3. Update the button icon
                if (newTheme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            });
        }
    }

    /**
     * Loads the saved theme from localStorage when the page loads.
     */
    function loadInitialTheme() {
        const savedTheme = localStorage.getItem('theme');
        const themeToggleButton = document.getElementById('theme-toggle-btn');
        const icon = themeToggleButton ? themeToggleButton.querySelector('i') : null;

        if (savedTheme === 'dark' && icon) {
            document.body.classList.add('dark-mode');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        }
    }

    /**
     * Toggles the user profile dropdown menu.
     */
    function initDropdownMenu() {
        const profileButton = document.getElementById('profileBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');
        if (profileButton && dropdownMenu) {
            profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownMenu.classList.toggle('active');
            });
            document.addEventListener('click', () => {
                dropdownMenu.classList.remove('active');
            });
        }
    }

    /**
     * Manages the logout confirmation modal.
     */
    function initLogoutModal() {
        const logoutLink = document.getElementById('logout-link');
        const modal = document.getElementById('custom-logout-modal');
        const confirmBtn = document.getElementById('confirm-logout-btn');
        const cancelBtn = document.getElementById('cancel-logout-btn');
        const closeBtn = modal ? modal.querySelector('.close-button') : null;

        if (!logoutLink || !modal || !confirmBtn || !cancelBtn || !closeBtn) return;

        const showModal = (shouldShow) => {
            if (shouldShow) {
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            } else {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }
        };

        logoutLink.addEventListener('click', (e) => {
            e.preventDefault();
            showModal(true);
        });

        confirmBtn.addEventListener('click', () => {
            window.location.href = '/dailyfix/admin/logout.php';
        });

        cancelBtn.addEventListener('click', () => showModal(false));
        closeBtn.addEventListener('click', () => showModal(false));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) showModal(false);
        });
    }
    
    /**
     * Handles animations for dashboard elements like counters and fly-ins.
     */
    function initDashboardAnimations() {
        // Animate counting numbers on stat cards
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(number => {
            const target = parseInt(number.getAttribute('data-target'), 10);
            if (isNaN(target)) return;
            
            let current = 0;
            const increment = Math.max(1, Math.ceil(target / 100));

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    number.textContent = target;
                    clearInterval(timer);
                } else {
                    number.textContent = Math.floor(current);
                }
            }, 50);
        });

        // Animate sections into view on scroll
        const animatedSections = document.querySelectorAll('.section-fly-in');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animatedSections.forEach(section => observer.observe(section));
    }

    /**
     * Handles initial page load visual effects.
     */
    function initPageLoad() {
        document.body.style.opacity = '1';
    }

    function initConfirmationModal() {
        const modal = document.getElementById('confirmation-modal');
        if (!modal) return;

        const modalTitle = modal.querySelector('#modal-title');
        const modalDescription = modal.querySelector('#modal-description');
        const modalIcon = modal.querySelector('.modal-icon i');
        const confirmBtn = modal.querySelector('#confirm-action-btn');
        const cancelBtn = modal.querySelector('#cancel-action-btn');
        const closeBtn = modal.querySelector('.close-button');
        let actionUrl = '';

        const showModal = (shouldShow) => {
            if (shouldShow) {
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            } else {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }
        };

        document.body.addEventListener('click', function(e) {
            const trigger = e.target.closest('.action-trigger');
            if (trigger) {
                e.preventDefault();

                // Get data from the clicked link
                actionUrl = trigger.getAttribute('href');
                const title = trigger.dataset.modalTitle;
                const description = trigger.dataset.modalDescription;
                const iconClass = trigger.dataset.modalIcon;
                const theme = trigger.dataset.modalTheme; // 'modal-danger' or 'modal-warning'
                const confirmText = trigger.dataset.modalConfirmText;

                // Update modal content
                modalTitle.textContent = title;
                modalDescription.textContent = description;
                confirmBtn.textContent = confirmText;
                modalIcon.className = iconClass; // e.g. "fas fa-trash"
                
                // Update modal theme
                modal.classList.remove('modal-danger', 'modal-warning');
                modal.classList.add(theme);

                showModal(true);
            }
        });
        
        confirmBtn.addEventListener('click', () => {
            if (actionUrl) {
                window.location.href = actionUrl;
            }
        });

        cancelBtn.addEventListener('click', () => showModal(false));
        closeBtn.addEventListener('click', () => showModal(false));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) showModal(false);
        });
    }

    function initBookingDetailsModal() {
        const modal = document.getElementById('booking-details-modal');
        if (!modal) return;

        const modalBody = modal.querySelector('.modal-body');
        const closeBtn = modal.querySelector('.close-button');
        
        const showModal = (shouldShow) => {
            modal.classList.toggle('show', shouldShow);
            modal.setAttribute('aria-hidden', !shouldShow);
        };

        document.body.addEventListener('click', function(e) {
            const trigger = e.target.closest('.view-details-btn');
            if (trigger) {
                e.preventDefault();
                const bookingId = trigger.dataset.bookingId;
                
                // Show modal with loading state
                modalBody.innerHTML = '<p style="text-align: center; color: var(--text-secondary);"><i class="fas fa-spinner fa-spin"></i> Loading details...</p>';
                showModal(true);

                // Fetch details from the API
                fetch(`/dailyfix/api/get_booking_details.php?id=${bookingId}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data) {
                            const data = result.data;
                            
                            // FIX: Use the new local time field from the API
                            const bookingTimeLocal = new Date(data.booking_time_local).toLocaleString('en-US', { dateStyle: 'full', timeStyle: 'short' });
                            const createdAt = new Date(data.created_at).toLocaleString('en-US', { dateStyle: 'medium' });

                            modalBody.innerHTML = `
                                <div class="details-section">
                                    <h3><i class="fas fa-users"></i> Participant Details</h3>
                                    <div class="details-grid">
                                        <div class="detail-item">
                                            <strong>Customer Name</strong>
                                            <span>${data.customer_name}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Worker Name</strong>
                                            <span>${data.worker_name}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Customer Email</strong>
                                            <span>${data.customer_email || 'N/A'}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Worker Email</strong>
                                            <span>${data.worker_email || 'N/A'}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Customer Phone</strong>
                                            <span>${data.customer_phone || 'N/A'}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Worker Phone</strong>
                                            <span>${data.worker_phone || 'N/A'}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="details-section">
                                    <h3><i class="fas fa-info-circle"></i> Booking Information</h3>
                                    <div class="details-grid">
                                        <div class="detail-item">
                                            <strong>Booking ID</strong>
                                            <span>#${data.id}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Status</strong>
                                            <span class="status-badge status-${data.status.toLowerCase().replace(' ', '_')}">${data.status}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Booking Time</strong>
                                            <span>${bookingTimeLocal}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Booked On</strong>
                                            <span>${createdAt}</span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Final Cost</strong>
                                            <span>${data.final_cost ? '$' + parseFloat(data.final_cost).toFixed(2) : 'Not Set'}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="details-section">
                                    <h3><i class="fas fa-tools"></i> Service Details</h3>
                                    <div class="service-details-box">
                                        ${data.service_details || 'No service details provided.'}
                                    </div>
                                </div>
                            `;
                        } else {
                            modalBody.innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Could not load booking details.</p>';
                        }
                    })
                    .catch(() => {
                        modalBody.innerHTML = '<p style="text-align: center; color: var(--danger-color);">An error occurred while fetching data.</p>';
                    });
            }
        });

        closeBtn.addEventListener('click', () => showModal(false));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) showModal(false);
        });
    }

    // Run the initialization
    init();
});