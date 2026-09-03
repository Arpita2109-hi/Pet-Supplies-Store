<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: signin.html'); exit(); }
if (($_SESSION['role'] ?? '') === 'admin') { header('Location: dashboard.php?preview=1'); exit(); }

$orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? ($_SESSION['pending_order_id'] ?? 0));
if ($orderId <= 0) { header('Location: checkout.php'); exit(); }

$sessionUserId = (int)$_SESSION['user_id'];
$orderStmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=? AND user_id=? LIMIT 1");
if (!$orderStmt) { die('Could not prepare order lookup: ' . mysqli_error($conn)); }
mysqli_stmt_bind_param($orderStmt, 'ii', $orderId, $sessionUserId);
mysqli_stmt_execute($orderStmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($orderStmt));
if (!$order) { header('Location: checkout.php'); exit(); }

if (($order['payment_status'] ?? 'pending') === 'paid') {
    header('Location: order_success.php?id=' . $orderId);
    exit();
}

$message = '';
$messageType = '';
$enteredName = trim($_POST['account_name'] ?? '');
$enteredAccount = trim($_POST['account_number'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $errors = [];
    if ($enteredName === '') $errors[] = 'Enter the bank account holder name.';
    if ($enteredAccount === '') $errors[] = 'Enter your bank account number.';
    if (!preg_match('/^\d{4}$/', $pin)) $errors[] = 'Enter your 4-digit bank PIN.';

    if (!$errors) {
        mysqli_begin_transaction($conn);
        try {
            $freshOrderStmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id=? AND user_id=? FOR UPDATE");
            mysqli_stmt_bind_param($freshOrderStmt, 'ii', $orderId, $sessionUserId);
            mysqli_stmt_execute($freshOrderStmt);
            $freshOrder = mysqli_fetch_assoc(mysqli_stmt_get_result($freshOrderStmt));
            if (!$freshOrder) throw new Exception('Order not found.');
            if (($freshOrder['payment_status'] ?? 'pending') === 'paid') {
                mysqli_commit($conn);
                header('Location: order_success.php?id=' . $orderId);
                exit();
            }

            $accountStmt = mysqli_prepare($conn, "SELECT * FROM bank_accounts WHERE account_number=? AND account_name=? FOR UPDATE");
            mysqli_stmt_bind_param($accountStmt, 'ss', $enteredAccount, $enteredName);
            mysqli_stmt_execute($accountStmt);
            $account = mysqli_fetch_assoc(mysqli_stmt_get_result($accountStmt));
            if (!$account) throw new Exception('Bank account details do not match. Check the account holder name and account number.');

            $accountId = (int)$account['account_id'];
            $storedPin = (string)$account['pin_hash'];
            $pinValid = password_verify($pin, $storedPin);
            if (!$pinValid && preg_match('/^\d{4}$/', $storedPin)) {
                // Supports an existing plain demo PIN once, then upgrades it to a secure hash.
                $pinValid = hash_equals($storedPin, $pin);
                if ($pinValid) {
                    $newHash = password_hash($pin, PASSWORD_DEFAULT);
                    $upgradeStmt = mysqli_prepare($conn, "UPDATE bank_accounts SET pin_hash=? WHERE account_id=?");
                    mysqli_stmt_bind_param($upgradeStmt, 'si', $newHash, $accountId);
                    mysqli_stmt_execute($upgradeStmt);
                }
            }

            if (!$pinValid) {
                $failedType = 'DEBIT';
                $failedStatus = 'failed';
                $failedDescription = 'Failed PIN verification for Happy Paws Order #' . $orderId;
                $amount = (float)$freshOrder['total'];
                $currentBalance = (float)$account['balance'];
                $failedStmt = mysqli_prepare($conn, "INSERT INTO bank_transactions (account_id, order_id, transaction_type, amount, balance_after, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($failedStmt, 'iisddss', $accountId, $orderId, $failedType, $amount, $currentBalance, $failedDescription, $failedStatus);
                mysqli_stmt_execute($failedStmt);
                mysqli_commit($conn);
                $message = 'Incorrect bank PIN. Payment was not completed.';
                $messageType = 'error';
            } else {
                $amount = (float)$freshOrder['total'];
                $currentBalance = (float)$account['balance'];

                if ($currentBalance < $amount) {
                    $failedType = 'DEBIT';
                    $failedStatus = 'failed';
                    $failedDescription = 'Insufficient balance for Happy Paws Order #' . $orderId;
                    $failedStmt = mysqli_prepare($conn, "INSERT INTO bank_transactions (account_id, order_id, transaction_type, amount, balance_after, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($failedStmt, 'iisddss', $accountId, $orderId, $failedType, $amount, $currentBalance, $failedDescription, $failedStatus);
                    mysqli_stmt_execute($failedStmt);
                    mysqli_commit($conn);
                    $message = 'Insufficient balance. Payment could not be completed.';
                    $messageType = 'error';
                } else {
                    $newBalance = $currentBalance - $amount;
                    $updateBalanceStmt = mysqli_prepare($conn, "UPDATE bank_accounts SET balance=? WHERE account_id=?");
                    mysqli_stmt_bind_param($updateBalanceStmt, 'di', $newBalance, $accountId);
                    if (!mysqli_stmt_execute($updateBalanceStmt)) throw new Exception('Could not update bank balance.');

                    $type = 'DEBIT';
                    $status = 'successful';
                    $description = 'Payment for Happy Paws Order #' . $orderId;
                    $transactionStmt = mysqli_prepare($conn, "INSERT INTO bank_transactions (account_id, order_id, transaction_type, amount, balance_after, description, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($transactionStmt, 'iisddss', $accountId, $orderId, $type, $amount, $newBalance, $description, $status);
                    if (!mysqli_stmt_execute($transactionStmt)) throw new Exception('Could not save transaction.');
                    $transactionId = mysqli_insert_id($conn);

                    $paid = 'paid';
                    $orderStatus = 'pending';
                    $updateOrderStmt = mysqli_prepare($conn, "UPDATE orders SET payment_status=?, transaction_id=?, status=? WHERE id=? AND user_id=?");
                    mysqli_stmt_bind_param($updateOrderStmt, 'sisii', $paid, $transactionId, $orderStatus, $orderId, $sessionUserId);
                    if (!mysqli_stmt_execute($updateOrderStmt)) throw new Exception('Could not update order payment status.');

                    // A completed purchase should no longer remain in the customer's wishlist.
                    // Only products that are part of this paid order are removed.
                    $removeWishlistStmt = mysqli_prepare($conn, "DELETE w FROM wishlist w INNER JOIN order_items oi ON oi.product_id=w.product_id WHERE w.user_id=? AND oi.order_id=?");
                    if (!$removeWishlistStmt) throw new Exception('Could not prepare wishlist update.');
                    mysqli_stmt_bind_param($removeWishlistStmt, 'ii', $sessionUserId, $orderId);
                    if (!mysqli_stmt_execute($removeWishlistStmt)) throw new Exception('Could not update wishlist after payment.');

                    mysqli_commit($conn);
                    $_SESSION['cart'] = [];
                    unset($_SESSION['promo_code'], $_SESSION['pending_order_id']);
                    header('Location: order_success.php?id=' . $orderId);
                    exit();
                }
            }
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $message = $e->getMessage() ?: 'Payment could not be completed. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message = implode(' ', $errors);
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Payment - Happy Paws</title>
    <link rel="stylesheet" href="customer-commerce.css?v=13">
    <link rel="stylesheet" href="bank.css?v=1">
</head>
<body class="bank-page">
<main class="bank-shell">
    <section class="bank-card">
        <a class="bank-back" href="checkout.php">← Back to Checkout</a>
        <div class="bank-logo">🏦</div>
        <div class="bank-brand">Happy Paws Bank</div>
        <h1>Confirm Payment</h1>
        <p class="bank-subtitle">Enter the same account details stored in <strong>bank_accounts</strong>. After successful PIN verification, the order amount is deducted from the database balance automatically.</p>

        <?php if ($message): ?><div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="amount-box">
            <span>Amount to Pay</span>
            <strong>Rs. <?= number_format((float)$order['total'], 2) ?></strong>
        </div>

        <div class="bank-flow-note">
            <strong>Database payment:</strong> the amount above will be deducted from this bank account balance and a new row will be saved in <code>bank_transactions</code>.
        </div>

        <form method="post" autocomplete="off">
            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
            <div class="bank-field">
                <label for="account_name">Account Holder Name</label>
                <input id="account_name" name="account_name" value="<?= htmlspecialchars($enteredName) ?>" placeholder="e.g. Arpita" required>
                <small>Must match the <strong>account_name</strong> in your bank_accounts table.</small>
            </div>
            <div class="bank-field">
                <label for="account_number">Bank Account Number</label>
                <input id="account_number" name="account_number" value="<?= htmlspecialchars($enteredAccount) ?>" placeholder="e.g. HP10001" required>
            </div>
            <div class="bank-field">
                <label for="pin">4-Digit Bank PIN</label>
                <input id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" placeholder="••••" required>
                <small>The account name, account number and PIN are verified against <strong>bank_accounts</strong>.</small>
            </div>
            <button class="bank-pay-button" type="submit">Pay Rs. <?= number_format((float)$order['total'], 2) ?></button>
        </form>

        <div class="bank-security">🔒 Demo digital banking payment • No cash on delivery</div>
    </section>
</main>
</body>
</html>
