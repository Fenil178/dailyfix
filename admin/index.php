<?php
include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/../api/connect.php';

// --- Initialize All Dashboard Stats ---
$stats = [
    'customer_count' => 0,
    'worker_count' => 0,
    'booking_count' => 0,
    'active_bookings' => 0,
    'total_revenue' => 0,
    'average_rating' => 0
];
$recent_bookings = [];
$chart_data = ['labels' => [], 'data' => []];
$error_message = '';
$users_locations = [];

try {
    // Fetch main stat cards
    $stats['customer_count'] = $conn->query("SELECT COUNT(*) FROM public.users WHERE role = 'customer'")->fetchColumn();
    $stats['worker_count'] = $conn->query("SELECT COUNT(*) FROM public.users WHERE role = 'worker'")->fetchColumn();
    $stats['booking_count'] = $conn->query("SELECT COUNT(*) FROM public.bookings")->fetchColumn();
    $stats['active_bookings'] = $conn->query("SELECT COUNT(*) FROM public.bookings WHERE status IN ('confirmed', 'in_progress')")->fetchColumn();
    
    // Fetch total revenue from completed bookings
    $stats['total_revenue'] = $conn->query("SELECT COALESCE(SUM(final_cost), 0) FROM public.bookings WHERE status = 'completed'")->fetchColumn();

    // Fetch average rating, returning 0 if the reviews table is empty
    $stats['average_rating'] = round($conn->query("SELECT COALESCE(AVG(rating), 0) FROM public.reviews")->fetchColumn(), 2);

    // Fetch recent bookings
    $recent_bookings_stmt = $conn->query("
        SELECT b.service_details, b.status, u.full_name as customer_name
        FROM public.bookings b
        JOIN public.users u ON b.customer_id = u.id
        ORDER BY b.created_at DESC LIMIT 2
    ");
    $recent_bookings = $recent_bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch data for the last 7 days for the chart
    $chart_query = $conn->query("
        SELECT DATE(created_at) as booking_date, COUNT(*) as booking_count
        FROM public.bookings
        WHERE created_at >= NOW() - INTERVAL '7 days'
        GROUP BY DATE(created_at)
        ORDER BY booking_date ASC
    ");
    $chart_results = $chart_query->fetchAll(PDO::FETCH_ASSOC);

    // Prepare chart data for JavaScript
    foreach($chart_results as $row) {
        $chart_data['labels'][] = date("M d", strtotime($row['booking_date']));
        $chart_data['data'][] = $row['booking_count'];
    }

    // Fetch user locations for the map
    $users_locations_stmt = $conn->query("
        SELECT full_name, latitude, longitude, role
        FROM public.users
        WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    ");
    $users_locations = $users_locations_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $error_message = "Unable to load some dashboard data. Please check the logs.";
}
?>

<link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div class="page-header section-fly-in">
    <div>
        <h1>Welcome back, <?php echo htmlspecialchars($adminName); ?>!</h1>
        <p>Here's a snapshot of your platform's activity.</p>
    </div>
</div>

<?php if ($error_message): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>

<div class="stats-container section-fly-in">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-content">
            <h3>Total Customers</h3>
            <p class="stat-number" data-target="<?php echo $stats['customer_count']; ?>">0</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-hard-hat"></i></div>
        <div class="stat-content">
            <h3>Total Workers</h3>
            <p class="stat-number" data-target="<?php echo $stats['worker_count']; ?>">0</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="stat-content">
            <h3>Total Revenue</h3>
            <p class="stat-number" data-target="<?php echo $stats['total_revenue']; ?>">0</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-star"></i></div>
        <div class="stat-content">
            <h3>Average Rating</h3>
            <p class="stat-number" data-target="<?php echo $stats['average_rating']; ?>">0</p>
        </div>
    </div>
</div>

<div class="dashboard-grid section-fly-in">
    <div class="dashboard-card chart-card">
        <div class="card-header">
            <h2><i class="fas fa-map-marked-alt"></i> User Locations</h2>
        </div>
        <div class="card-content">
            <div id="user-map" style="height: 400px;"></div>
        </div>
    </div>
    
    <div class="dashboard-card chart-card">
        <div class="card-header">
            <h2><i class="fas fa-chart-bar"></i> Weekly Bookings</h2>
        </div>
        <div class="card-content">
            <canvas id="bookingsChart" style="height: 300px;"></canvas>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Recent Bookings</h2>
        </div>
        <div class="card-content">
            <div class="bookings-list">
                <?php if (empty($recent_bookings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No recent bookings found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <div class="booking-service">
                                    <i class="fas fa-tools"></i>
                                    <span><?php echo htmlspecialchars($booking['service_details']); ?></span>
                                </div>
                                <div class="booking-customer">
                                    <i class="fas fa-user"></i>
                                    <span>For <?php echo htmlspecialchars($booking['customer_name'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="booking-status">
                                <?php $status_class = strtolower(str_replace(' ', '_', $booking['status'])); ?>
                                <span class="status-badge status-<?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="view_bookings.php" class="btn btn-full-width">View All Bookings</a>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div class="card-content">
            <div class="quick-actions-list">
                <a href="manage_services.php" class="quick-action-item">
                    <div class="action-icon"><i class="fas fa-toolbox"></i></div>
                    <div class="action-text">
                        <strong>Manage Services</strong>
                        <span>Add or edit main categories</span>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="manage_sub_services.php" class="quick-action-item">
                    <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="action-text">
                        <strong>Manage Sub-Services</strong>
                        <span>Add or edit specific services</span>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('bookingsChart');
    if (ctx) {
        const chartLabels = <?php echo json_encode($chart_data['labels']); ?>;
        const chartData = <?php echo json_encode($chart_data['data']); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Bookings',
                    data: chartData,
                    backgroundColor: 'rgba(79, 70, 229, 0.7)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    const userMap = document.getElementById('user-map');
    if (userMap) {
        const map = L.map('user-map').setView([21.1702, 72.8311], 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const users = <?php echo json_encode($users_locations); ?>;
        users.forEach(user => {
            const marker = L.marker([user.latitude, user.longitude]).addTo(map);
            marker.bindPopup(`<b>${user.full_name}</b><br>${user.role}`);
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>