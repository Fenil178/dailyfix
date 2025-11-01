<?php
include_once __DIR__ . "/../api/connect.php";
include_once __DIR__ . "/../api/header.php";

// Security: Ensure the user is a worker
if ($role !== 'worker') {
    header("Location: /dailyfix/dashboard.php");
    exit;
}

$wallet_details = null;
$payouts = [];

try {
    // Fetch wallet and transaction details
    $stmt = $conn->prepare("SELECT * FROM public.wallets WHERE worker_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($wallet) {
        // Fetch all transactions for this wallet
        $stmt = $conn->prepare("SELECT * FROM public.transactions WHERE wallet_id = ? ORDER BY created_at DESC");
        $stmt->execute([$wallet['id']]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch pending and completed payouts
        $stmt_payouts = $conn->prepare("SELECT * FROM public.payouts WHERE wallet_id = ? ORDER BY requested_at DESC");
        $stmt_payouts->execute([$wallet['id']]);
        $payouts = $stmt_payouts->fetchAll(PDO::FETCH_ASSOC);
        
        $wallet_details = ['wallet' => $wallet, 'transactions' => $transactions];
    } else {
        // If wallet doesn't exist, create one
        $stmt_create_wallet = $conn->prepare("INSERT INTO public.wallets (worker_id) VALUES (?) RETURNING id, balance");
        $stmt_create_wallet->execute([$userId]);
        $new_wallet = $stmt_create_wallet->fetch(PDO::FETCH_ASSOC);
        $wallet_details = ['wallet' => $new_wallet, 'transactions' => []];
    }
} catch (PDOException $e) {
    error_log("Wallet page error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Wallet - DailyFix</title>
    <link rel="stylesheet" href="/dailyfix/assets/css/index.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/management.css" />
    <link rel="stylesheet" href="/dailyfix/assets/css/scrollbar_hidden.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <script defer src="/dailyfix/assets/js/app.js"></script>
    <link rel="icon" type="image/png" href="/dailyfix/assets/images/logo.png">
    <style>
        /* Common skeleton styles (loader, shimmer, dark-mode) */
        .skeleton-loader {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background-color: var(--background-color-body, #f9f9f9);
            z-index: 9999; opacity: 1; transition: opacity 0.5s ease;
        }
        .skeleton-loader.hidden { opacity: 0; pointer-events: none; }
        .skeleton-container {
            max-width: 1100px; width: 100%;
            padding: 0 1rem;
            margin: 1rem auto;
            margin-top: 80px; /* Adjust to match your header's height */
        }
        @keyframes shimmer { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
        .skeleton {
            animation: shimmer 1.5s infinite linear;
            background: linear-gradient(to right, 
            var(--hover-color, #f0f0f0) 8%, 
            var(--border-color, #e2e8f0) 18%, 
            var(--hover-color, #f0f0f0) 33%);
            background-size: 800px 104px; border-radius: 6px;
        }

        /* Page-specific skeleton layout for wallet.php */
        .skeleton-title { height: 38px; width: 300px; margin: 2rem 0; }
        .skeleton-wallet-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
        }
        .skeleton-card {
            padding: 1.5rem;
            background-color: var(--background-color-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 8px;
        }
        .skeleton-balance-card {
            height: 200px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .skeleton-balance-title { height: 20px; width: 150px; margin-bottom: 1rem; }
        .skeleton-balance-amount { height: 40px; width: 200px; margin-bottom: 1.5rem; }
        .skeleton-balance-button { height: 45px; width: 100%; }

        .skeleton-list-title { height: 24px; width: 40%; margin-bottom: 1.5rem; }
        .skeleton-list-item { height: 40px; width: 100%; margin-bottom: 1rem; }

        @media (max-width: 900px) {
            .skeleton-wallet-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="skeleton-loader" id="page-loader">
        <div class="skeleton-container">
            <div class="skeleton skeleton-title"></div>
            <div class="skeleton-wallet-grid">
            <div class="skeleton-card skeleton-balance-card">
                <div class="skeleton skeleton-balance-title"></div>
                <div class="skeleton skeleton-balance-amount"></div>
                <div class="skeleton skeleton-balance-button"></div>
            </div>
            <div class="skeleton-card" style="height: 400px;">
                <div class="skeleton skeleton-list-title"></div>
                <div class="skeleton skeleton-list-item"></div>
                <div class="skeleton skeleton-list-item"></div>
                <div class="skeleton skeleton-list-item"></div>
                <div class="skeleton skeleton-list-item"></div>
            </div>
            </div>
        </div>
    </div>
    <main class="page-content">
        <div class="management-container">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2rem;">
                <h1 class="page-title" style="margin-bottom: 0;">My Wallet</h1>
                <a href="/dailyfix/worker/earnings.php" class="btn btn-main" style="text-decoration:none;">View Earnings History</a>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <h4>Available Balance</h4>
                    <p><i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($wallet_details['wallet']['balance'] ?? 0, 2); ?></p>
                </div>
                <div class="summary-card">
                    <h4>Request a Payout</h4>
                    <p style="font-size: 1rem; color: var(--text-color-light); margin-bottom: 1rem;">Click below to request a payout of your entire available balance.</p>
                    <button id="request-payout-btn" class="btn-main" <?php echo (($wallet_details['wallet']['balance'] ?? 0) <= 0) ? 'disabled' : ''; ?>>
                        Request Payout
                    </button>
                </div>
            </div>
            
            <h2 class="item-list-header">Transaction History</h2>
            <div class="item-list">
                <?php if (!empty($wallet_details['transactions'])): ?>
                    <?php foreach ($wallet_details['transactions'] as $tx): ?>
                        <div class="list-item">
                            <div class="item-details">
                                <p><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $tx['type']))); ?></strong></p>
                                <p style="color: var(--text-color-light); font-size: 0.9rem;"><?php echo htmlspecialchars($tx['description']); ?></p>
                                <small><?php echo date("M d, Y, h:i A", strtotime($tx['created_at'])); ?></small>
                            </div>
                            <div class="item-value" style="color: <?php echo $tx['type'] === 'credit' ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo $tx['type'] === 'credit' ? '+' : '-'; ?><i class="fa-solid fa-indian-rupee-sign"></i><?php echo number_format($tx['amount'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>No transactions yet.</h3>
                        <p>Your earnings and payouts will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script>
        const requestPayoutBtn = document.getElementById('request-payout-btn');
        if (requestPayoutBtn) {
            requestPayoutBtn.addEventListener('click', function() {
                if (!confirm('Are you sure you want to request a payout for your entire balance? This action cannot be undone.')) {
                    return;
                }
                
                this.disabled = true;
                this.textContent = 'Processing...';

                fetch('/dailyfix/api/request-payout.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            this.disabled = false;
                            this.textContent = 'Request Payout';
                        }
                    })
                    .catch(() => {
                        alert('An error occurred. Please try again.');
                        this.disabled = false;
                        this.textContent = 'Request Payout';
                    });
            });
        }
    </script>
    <?php include_once __DIR__ . "/../api/footer.php"; ?>
</body>
</html>