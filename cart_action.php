<?php
session_start();
require_once 'db.php';
require_once 'cart_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.html');
    exit();
}
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: dashboard.php?preview=1');
    exit();
}

ensureCart();
$action = $_POST['action'] ?? $_GET['action'] ?? 'add';
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit();
}

if ($productId <= 0) {
    header('Location: cart.php');
    exit();
}

switch ($action) {
    case 'add':
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
        break;
    case 'set':
        $_SESSION['cart'][$productId] = $quantity;
        break;
    case 'increase':
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
        break;
    case 'decrease':
        $newQty = ($_SESSION['cart'][$productId] ?? 1) - 1;
        if ($newQty <= 0) unset($_SESSION['cart'][$productId]);
        else $_SESSION['cart'][$productId] = $newQty;
        break;
    case 'remove':
        unset($_SESSION['cart'][$productId]);
        break;
}
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json');

    echo json_encode([
        'success' => true,
        'cartCount' => cartCount()
    ]);

    exit();
}

$redirect = $_POST['redirect'] ?? 'cart.php';
if (!in_array($redirect, ['cart.php', 'wishlist_dashboard.php', 'dashboard.php'], true)) {
    $redirect = 'cart.php';
}
header('Location: ' . $redirect);
exit();
?>
