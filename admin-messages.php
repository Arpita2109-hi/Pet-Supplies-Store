<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || strtolower($_SESSION["role"] ?? "") !== "admin") {
    header("Location: signin.html");
    exit();
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id > 0 && $action === 'read') {
        $stmt = mysqli_prepare($conn, "UPDATE contact_messages SET status='read' WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
    } elseif ($id > 0 && $action === 'unread') {
        $stmt = mysqli_prepare($conn, "UPDATE contact_messages SET status='unread' WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
    } elseif ($id > 0 && $action === 'delete') {
        $stmt = mysqli_prepare($conn, "DELETE FROM contact_messages WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: admin-messages.php");
    exit();
}

$messages = mysqli_query($conn, "SELECT * FROM contact_messages ORDER BY created_at DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Messages | Happy Paws</title>
    <link rel="stylesheet" href="admin-header.css?v=1">
    <link rel="stylesheet" href="admin-messages.css?v=1">
</head>
<body>
<?php
$pageTitle = "Customer Messages";
$headerButtonText = "Admin Dashboard";
$headerButtonLink = "admin-dashboard.php";
include "admin-header.php";
?>

<main class="messages-page">
    <div class="messages-heading">
        <div>
            <span class="eyebrow">CONTACT US</span>
            <h2>Customer Messages</h2>
            <p>Messages submitted from the Happy Paws Contact Us form.</p>
        </div>
    </div>

    <div class="messages-card">
        <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($messages)): ?>
                        <tr class="<?= $row['status'] === 'unread' ? 'unread-row' : '' ?>">
                            <td>#<?= (int)$row['id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><a href="mailto:<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></a></td>
                            <td class="message-text"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                            <td><span class="status <?= htmlspecialchars($row['status']) ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></span></td>
                            <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))) ?></td>
                            <td>
                                <div class="actions">
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <?php if ($row['status'] === 'unread'): ?>
                                            <button type="submit" name="action" value="read" class="read-btn">Mark Read</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="unread" class="read-btn secondary">Mark Unread</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Delete this message?');">
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" name="action" value="delete" class="delete-btn">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">✉</div>
                <h3>No messages yet</h3>
                <p>Customer Contact Us messages will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
