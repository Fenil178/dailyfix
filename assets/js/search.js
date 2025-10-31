document.addEventListener('DOMContentLoaded', () => {
    // --- Get All Elements ---
    const searchModal = document.getElementById('search-modal');
    if (!searchModal) return; // Stop if search modal isn't on the page

    const searchInput = document.getElementById('search-modal-input');
    const searchCloseBtn = document.getElementById('search-modal-close');
    
    // Containers
    const recentSearchesContainer = document.getElementById('search-recent');
    const recentSearchesList = document.getElementById('search-recent-list');
    const recentSearchesEmpty = document.getElementById('search-recent-empty');
    const tabContentContainer = document.getElementById('search-tab-content');
    const noResultsContainer = document.getElementById('search-no-results');
    const noResultsTerm = document.getElementById('search-no-results-term');

    // Tabs
    const tabs = document.querySelectorAll('.search-tab');
    const servicesTab = document.querySelector('.search-tab[data-tab="services"]');
    const workersTab = document.querySelector('.search-tab[data-tab="workers"]');
    // const customersTab = document.querySelector('.search-tab[data-tab="customers"]'); // No longer needed
    const navigationTab = document.querySelector('.search-tab[data-tab="navigation"]'); 
    
    // Tab Panels
    const servicesPanel = document.getElementById('search-tab-services');
    const workersPanel = document.getElementById('search-tab-workers');
    // const customersPanel = document.getElementById('search-tab-customers'); // No longer needed
    const navigationPanel = document.getElementById('search-tab-navigation'); 
    
    // Result Lists
    const servicesResultsList = document.getElementById('search-results-services');
    const workersResultsList = document.getElementById('search-results-workers');
    // const customersResultsList = document.getElementById('search-results-customers'); // No longer needed
    const navigationResultsList = document.getElementById('search-results-navigation'); 

    // Recent Searches Storage
    const RECENT_SEARCH_KEY = 'dailyfix_recent_searches';
    let recentSearches = JSON.parse(localStorage.getItem(RECENT_SEARCH_KEY)) || [];

    // --- Debounce Function ---
    let debounceTimer;
    const debounce = (func, delay) => {
        return function(...args) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    };

    // --- Render Functions ---
    const renderRecentSearches = () => {
        if (!recentSearchesList || !recentSearchesEmpty) return;
        
        recentSearchesList.innerHTML = '';
        if (recentSearches.length === 0) {
            recentSearchesEmpty.style.display = 'block';
        } else {
            recentSearchesEmpty.style.display = 'none';
            recentSearches.forEach(term => {
                const li = document.createElement('li');
                li.innerHTML = `<a href="#" data-term="${escapeHTML(term)}">${escapeHTML(term)}</a><i class="fas fa-times" data-term="${escapeHTML(term)}"></i>`;
                recentSearchesList.appendChild(li);
            });
        }
    };

    const renderResults = (data) => {
        // Clear all lists
        if (servicesResultsList) servicesResultsList.innerHTML = '';
        if (workersResultsList) workersResultsList.innerHTML = '';
        if (navigationResultsList) navigationResultsList.innerHTML = ''; 
        
        const hasServices = data.services && data.services.length > 0;
        const hasWorkers = data.workers && data.workers.length > 0;
        const hasNavigation = data.navigation && data.navigation.length > 0;

        if (!hasServices && !hasWorkers && !hasNavigation) { // <-- UPDATED
            noResultsContainer.style.display = 'block';
            noResultsTerm.textContent = searchInput.value;
            tabContentContainer.style.display = 'none';
        } else {
            noResultsContainer.style.display = 'none';
            tabContentContainer.style.display = 'block';

            // Render Services
            if (hasServices && servicesResultsList) {
                data.services.forEach(service => {
                    const li = document.createElement('li');
                    let href = '#';
                    let icon = 'fa-tools';
                    
                    if (service.type === 'Category') {
                        href = `/dailyfix/customer/services.php`; 
                        icon = 'fa-concierge-bell';
                    } else if (service.type === 'Sub-Service') {
                        href = `/dailyfix/customer/find_workers.php?service=${service.slug}`;
                        icon = 'fa-toolbox';
                    } else if (service.type === 'Service Item') {
                        href = `/dailyfix/customer/services.php`; 
                        icon = 'fa-list-ul';
                    }
                    
                    li.innerHTML = `
                        <a href="${href}" data-slug="${service.slug}" data-type="${service.type}">
                            <i class="fas ${icon}"></i>
                            <div>
                                <strong>${escapeHTML(service.name)}</strong>
                                <span>${escapeHTML(service.type)}</span>
                            </div>
                        </a>`;
                    servicesResultsList.appendChild(li);
                });
            }

            // Render Workers
            if (hasWorkers && workersResultsList) {
                data.workers.forEach(worker => {
                    const li = document.createElement('li');
                    const href = `/dailyfix/customer/view_worker_services.php?worker_id=${worker.id}`;
                    
                    li.innerHTML = `
                        <a href="${href}">
                            <img src="${escapeHTML(worker.profile_image)}" alt="${escapeHTML(worker.full_name)}">
                            <div>
                                <strong>${escapeHTML(worker.full_name)}</strong>
                                <span>Worker</span>
                            </div>
                        </a>`;
                    workersResultsList.appendChild(li);
                });
            }
            
            // --- UPDATED: Render Navigation (simplified) ---
            if (hasNavigation && navigationResultsList) {
                data.navigation.forEach(nav => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <a href="${escapeHTML(nav.url)}">
                            <i class="fas ${escapeHTML(nav.icon)}"></i>
                            <div>
                                <strong>${escapeHTML(nav.name)}</strong>
                            </div>
                        </a>`;
                    navigationResultsList.appendChild(li);
                });
            }
            
            // --- REMOVED: Render Customers ---
            
            // Update tab counts
            if (servicesTab) servicesTab.textContent = `Services (${data.services.length})`;
            if (workersTab) workersTab.textContent = `Workers (${data.workers.length})`;
            if (navigationTab) navigationTab.textContent = `Navigation (${data.navigation.length})`;

            // --- UPDATED: Prioritize which tab to show ---
            const activeTab = document.querySelector('.search-tab.active');
            const activeTabName = activeTab ? activeTab.dataset.tab : '';

            if (
                (activeTabName === 'services' && hasServices) ||
                (activeTabName === 'navigation' && hasNavigation) ||
                (activeTabName === 'workers' && hasWorkers)
            ) {
                // Current tab is fine, no change
            } else {
                // Find a new tab to activate, in order of priority
                if (hasServices) activateTab(servicesTab);
                else if (hasNavigation) activateTab(navigationTab);
                else if (hasWorkers) activateTab(workersTab);
            }
        }
    };

    // --- State Management Functions ---
    const showRecentSearches = () => {
        recentSearchesContainer.style.display = 'block';
        tabContentContainer.style.display = 'none';
        noResultsContainer.style.display = 'none';
        renderRecentSearches();
    };

    const showResults = () => {
        recentSearchesContainer.style.display = 'none';
        tabContentContainer.style.display = 'block';
    };

    const addRecentSearch = (term) => {
        if (!term) return;
        recentSearches = recentSearches.filter(t => t.toLowerCase() !== term.toLowerCase());
        recentSearches.unshift(term);
        recentSearches = recentSearches.slice(0, 5);
        localStorage.setItem(RECENT_SEARCH_KEY, JSON.stringify(recentSearches));
        renderRecentSearches();
    };

    const removeRecentSearch = (term) => {
        recentSearches = recentSearches.filter(t => t.toLowerCase() !== term.toLowerCase());
        localStorage.setItem(RECENT_SEARCH_KEY, JSON.stringify(recentSearches));
        renderRecentSearches();
    };

    const activateTab = (clickedTab) => {
        if (!clickedTab) return; // Safety check
        tabs.forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.search-tab-panel').forEach(panel => panel.classList.remove('active'));
        
        clickedTab.classList.add('active');
        const panelId = `search-tab-${clickedTab.dataset.tab}`;
        document.getElementById(panelId)?.classList.add('active');
    };
    
    const resetSearch = () => {
        searchInput.value = '';
        if (servicesResultsList) servicesResultsList.innerHTML = '';
        if (workersResultsList) workersResultsList.innerHTML = '';
        if (navigationResultsList) navigationResultsList.innerHTML = ''; 
        
        if (servicesTab) servicesTab.textContent = 'Services';
        if (workersTab) workersTab.textContent = 'Workers';
        if (navigationTab) navigationTab.textContent = 'Navigation';
        
        // --- UPDATED: Default to the correct first tab based on role ---
        if (servicesTab) activateTab(servicesTab); // For customers
        if (navigationTab) activateTab(navigationTab); // For workers
        
        showRecentSearches();
    };

    // --- API Call ---
    const performSearch = async (query) => {
        if (query.length < 2) {
            showRecentSearches();
            return;
        }
        try {
            const response = await fetch(`/dailyfix/api/live_search.php?q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            showResults();
            renderResults(data);
        } catch (error) {
            console.error('Search fetch error:', error);
            noResultsContainer.style.display = 'block';
            noResultsTerm.textContent = query;
            tabContentContainer.style.display = 'none';
        }
    };

    // --- Event Listeners ---
    
    searchInput.addEventListener('input', debounce(() => {
        performSearch(searchInput.value.trim());
    }, 300));

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const term = searchInput.value.trim();
            if (term) {
                addRecentSearch(term);
                performSearch(term);
            }
        }
    });

    // Listen for custom reset event from header.php
    document.addEventListener('search:reset', resetSearch);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => activateTab(tab));
    });

    recentSearchesList.addEventListener('click', (e) => {
        e.preventDefault();
        const term = e.target.dataset.term;
        if (!term) return;

        if (e.target.tagName === 'A') {
            searchInput.value = term;
            performSearch(term);
        } else if (e.target.tagName === 'I') {
            removeRecentSearch(term);
        }
    });

    if (servicesResultsList) {
        servicesResultsList.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && link.dataset.type === 'Category') {
                e.preventDefault();
                if (typeof filterServices === 'function') { // Check if services.js function exists
                    filterServices(link.dataset.slug);
                    document.getElementById('search-modal-close').click();
                } else {
                    window.location.href = link.href;
                }
            }
        });
    }

    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return str.toString().replace(/[&<>"']/g, match => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[match]);
    }

    showRecentSearches();
});