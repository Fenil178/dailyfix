<?php
header('Content-Type: application/json');
include_once __DIR__ . "/connect.php";
include_once __DIR__ . "/user_session.php"; // Provides $userId, $role

// 1. Get and sanitize the search query
$query = $_GET['q'] ?? '';
if (strlen($query) < 2) {
    // Don't search for less than 2 characters
    echo json_encode(['services' => [], 'workers' => [], 'navigation' => []]);
    exit;
}
$search_term = '%' . strtolower($query) . '%';

$response = [
    'services' => [],
    'workers' => [],
    'navigation' => [] // Worker nav
];

try {
    // 2. Role-based search
    if ($role === 'customer') {
        
        // --- SERVICE SEARCH (Only for Customers) ---
        $stmt_services = $conn->prepare("
            (
                SELECT id, name, slug, 'Category' as type
                FROM public.services
                WHERE LOWER(name) ILIKE ?
            )
            UNION
            (
                SELECT id, name, slug, 'Sub-Service' as type
                FROM public.sub_services
                WHERE LOWER(name) ILIKE ?
            )
            UNION
            (
                SELECT id, name, slug, 'Service Item' as type
                FROM public.sub_service_items
                WHERE LOWER(name) ILIKE ?
            )
            ORDER BY name ASC
            LIMIT 6
        ");
        $stmt_services->execute([$search_term, $search_term, $search_term]);
        $response['services'] = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

        // --- WORKER SEARCH (For Customers) ---
        $stmt_workers = $conn->prepare("
            SELECT id, full_name, profile_image
            FROM public.users
            WHERE role = 'worker'
              AND account_status = 'active'
              AND LOWER(full_name) ILIKE ?
            ORDER BY full_name ASC
            LIMIT 6
        ");
        $stmt_workers->execute([$search_term]);
        
        while ($worker = $stmt_workers->fetch(PDO::FETCH_ASSOC)) {
            $avatar_url = $worker['profile_image'] ?: 'assets/images/default-avatar.png';
            if ($worker['profile_image'] && strpos($worker['profile_image'], '/') !== 0) {
                $avatar_url = '/dailyfix/' . $avatar_url;
            }
            $worker['profile_image'] = $avatar_url;
            $response['workers'][] = $worker;
        }

    } elseif ($role === 'worker') {
        
        // --- UPDATED: Worker Navigation Search (Expanded List, No Description) ---
        $nav_links = [
            ['name' => 'Dashboard', 'url' => '/dailyfix/dashboard.php', 'icon' => 'fa-home'],
            ['name' => 'Job Requests', 'url' => '/dailyfix/worker/jobs.php', 'icon' => 'fa-briefcase'],
            ['name' => 'New Requests', 'url' => '/dailyfix/worker/jobs.php?status=pending', 'icon' => 'fa-inbox'],
            ['name' => 'Active Jobs', 'url' => '/dailyfix/worker/jobs.php?status=accepted', 'icon' => 'fa-cogs'],
            ['name' => 'Completed Jobs', 'url' => '/dailyfix/worker/jobs.php?status=completed', 'icon' => 'fa-check-circle'],
            ['name' => 'My Earnings', 'url' => '/dailyfix/worker/earnings.php', 'icon' => 'fa-indian-rupee-sign'],
            ['name' => 'Payouts', 'url' => '/dailyfix/worker/earnings.php', 'icon' => 'fa-credit-card'],
            ['name' => 'My Reports', 'url' => '/dailyfix/worker/reports.php', 'icon' => 'fa-chart-bar'],
            ['name' => 'Financial Reports', 'url' => '/dailyfix/worker/reports.php', 'icon' => 'fa-file-invoice-dollar'],
            ['name' => 'My Profile', 'url' => '/dailyfix/profile.php', 'icon' => 'fa-user-circle'],
            ['name' => 'Edit Profile', 'url' => '/dailyfix/profile.php', 'icon' => 'fa-user-edit'],
            ['name' => 'Change Password', 'url' => '/dailyfix/profile.php', 'icon' => 'fa-key'],
            ['name' => 'My Services', 'url' => '/dailyfix/worker/manage_services.php', 'icon' => 'fa-concierge-bell'],
            ['name' => 'Add New Service', 'url' => '/dailyfix/worker/manage_services.php', 'icon' => 'fa-plus'],
            ['name' => 'Help & Support', 'url' => '/dailyfix/contact.php', 'icon' => 'fa-question-circle'],
        ];

        // Use str_replace to remove SQL wildcards for PHP string search
        $search_needle = strtolower(str_replace('%', '', $search_term));
        
        foreach ($nav_links as $link) {
            // Search in name only
            if (strpos(strtolower($link['name']), $search_needle) !== false) {
                $response['navigation'][] = $link;
            }
        }
        // Limit results
        $response['navigation'] = array_slice($response['navigation'], 0, 6);

        // --- REMOVED: Customer search for workers is gone ---
    }

} catch (PDOException $e) {
    error_log("Live Search Error: " . $e->getMessage());
}

// 4. Return the combined JSON response
echo json_encode($response);