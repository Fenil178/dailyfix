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
</head>
<body>
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