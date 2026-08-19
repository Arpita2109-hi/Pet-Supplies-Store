<?php
function ensureCart(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function cartCount(): int {
    ensureCart();
    return array_sum(array_map('intval', $_SESSION['cart']));
}

function getCartProducts(mysqli $conn): array {
    ensureCart();
    $items = [];
    $ids = array_values(array_filter(array_map('intval', array_keys($_SESSION['cart'])), fn($id) => $id > 0));
    if (!$ids) {
        return $items;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id IN ($placeholders)");
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $id = (int)$row['id'];
        $qty = max(1, (int)($_SESSION['cart'][$id] ?? 1));
        $row['quantity'] = $qty;
        $row['line_total'] = (float)$row['price'] * $qty;
        $items[] = $row;
    }

    usort($items, function ($a, $b) use ($ids) {
        return array_search((int)$a['id'], $ids, true) <=> array_search((int)$b['id'], $ids, true);
    });
    return $items;
}

function cartSubtotal(array $items): float {
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float)$item['line_total'];
    }
    return $subtotal;
}

function promoDiscount(float $subtotal, string $code): float {
    $code = strtoupper(trim($code));
    if ($code === 'HAPPY10') {
        return round($subtotal * 0.10, 2);
    }
    if ($code === 'PAWS50' && $subtotal >= 500) {
        return 50.00;
    }
    return 0.00;
}

function promoDescription(string $code): string {
    $code = strtoupper(trim($code));
    if ($code === 'HAPPY10') return '10% off your order';
    if ($code === 'PAWS50') return 'Rs. 50 off orders above Rs. 500';
    return '';
}
?>
