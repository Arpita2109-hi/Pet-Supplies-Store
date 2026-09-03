<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header('Location: signin.html'); exit(); }
if (($_SESSION['role'] ?? '') === 'admin') { header('Location: dashboard.php?preview=1'); exit(); }

$userId = (int)$_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT bt.*, ba.account_number, ba.account_name, o.total AS order_total FROM bank_transactions bt JOIN bank_accounts ba ON ba.account_id=bt.account_id JOIN orders o ON o.id=bt.order_id WHERE o.user_id=? ORDER BY bt.transaction_date DESC, bt.transaction_id DESC");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Happy Paws</title>
    <link rel="stylesheet" href="dashboard.css?v=2">
    <link rel="stylesheet" href="customer-commerce.css?v=13">
    <link rel="stylesheet" href="bank.css?v=1">
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <a href="dashboard.php" class="logo"><span class="logo-icon">🐾</span><span class="logo-text">Happy Paws</span></a>
        <div></div>
        <nav class="account-navigation"><a href="dashboard.php">Shop</a><a href="cart.php">Cart</a><a href="checkout.php">Checkout</a><a href="transaction_history.php" class="active-link">My Purchases</a><a href="logout.php">Logout</a></nav>
    </div>
</header>
<main class="commerce-shell">
    <h1 class="page-title">Transaction History</h1>
    <p class="page-subtitle">Digital payments made through Happy Paws Bank.</p>
    <section class="panel transaction-panel">
        <?php if (mysqli_num_rows($transactions) === 0): ?>
            <div class="transaction-empty">No banking transactions yet. Your successful and failed payment attempts will appear here.</div>
        <?php else: ?>
        <div class="transaction-table-wrap">
            <table class="transaction-table">
                <thead><tr><th>Transaction</th><th>Account</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php while ($tx = mysqli_fetch_assoc($transactions)): ?>
                    <tr>
                        <td>#<?= (int)$tx['transaction_id'] ?></td>
                        <td><?= htmlspecialchars($tx['account_number']) ?></td>
                        <td><?= htmlspecialchars($tx['transaction_type']) ?></td>
                        <td>Rs. <?= number_format((float)$tx['amount'], 2) ?></td>
                        <td><span class="tx-status <?= $tx['status'] === 'successful' ? 'ok' : 'bad' ?>"><?= htmlspecialchars(ucfirst($tx['status'])) ?></span></td>
                        <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($tx['transaction_date']))) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
