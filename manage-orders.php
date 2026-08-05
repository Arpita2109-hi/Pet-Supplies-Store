<?php
session_start();
require_once "db.php";

if (
    !isset($_SESSION["user_id"]) ||
    strtolower($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: signin.html");
    exit();
}

if (
    isset($_GET["action"]) &&
    isset($_GET["id"])
) {
    $orderId = (int) $_GET["id"];
    $action = $_GET["action"];

    $allowedActions = [
        "process" => "processing",
        "complete" => "completed",
        "reject" => "rejected"
    ];

    if (isset($allowedActions[$action])) {
        $newStatus = $allowedActions[$action];

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders
             SET status = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $newStatus,
            $orderId
        );

        mysqli_stmt_execute($stmt);
    }

    header("Location: manage-orders.php");
    exit();
}

if (isset($_GET["delete"])) {
    $orderId = (int) $_GET["delete"];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM orders WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $orderId
    );

    mysqli_stmt_execute($stmt);

    header("Location: manage-orders.php?deleted=1");
    exit();
}


$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $like = "%" . $search . "%";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM orders
         WHERE customer_name LIKE ?
            OR customer_email LIKE ?
            OR status LIKE ?
            OR CAST(id AS CHAR) LIKE ?
         ORDER BY id DESC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $like,
        $like,
        $like,
        $like
    );

    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);

} else {
    $orders = mysqli_query(
        $conn,
        "SELECT * FROM orders ORDER BY id DESC"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Orders | Happy Paws</title>

    <link
        rel="stylesheet"
        href="manage-orders.css?v=10"
    >

    <link rel="stylesheet" href="admin-header.css?v=1">
</head>

<body>

<?php
$pageTitle = "Orders";
$headerButtonText = "Go to Dashboard";
$headerButtonLink = "admin-dashboard.php";
include "admin-header.php";
?>

<main class="container">

    <?php if (isset($_GET["deleted"])): ?>
        <p class="success-message">
            Order deleted successfully.
        </p>
    <?php endif; ?>

    <section class="orders-heading">

        <h2>Customer Orders</h2>

        <form
            method="GET"
            class="search-form"
        >
            <input
                type="text"
                name="search"
                placeholder="Search ID, customer, email or status"
                value="<?php
                echo htmlspecialchars($search);
                ?>"
            >

            <button type="submit">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a href="manage-orders.php">
                    Clear
                </a>
            <?php endif; ?>

        </form>

    </section>

    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php
            if (
                $orders &&
                mysqli_num_rows($orders) > 0
            ):
            ?>

                <?php
                while (
                    $order =
                    mysqli_fetch_assoc($orders)
                ):
                ?>

                    <tr>

                        <td>
                            #<?php
                            echo (int) $order["id"];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $order["customer_name"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $order["customer_email"]
                            );
                            ?>
                        </td>

                        <td>
                            Rs.
                            <?php
                            echo number_format(
                                (float) $order["total"],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <span class="
                                status
                                <?php
                                echo htmlspecialchars(
                                    $order["status"]
                                );
                                ?>
                            ">
                                <?php
                                echo htmlspecialchars(
                                    ucfirst($order["status"])
                                );
                                ?>
                            </span>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $order["created_at"]
                            );
                            ?>
                        </td>

                        <td class="actions">

                            <a
                                class="process-button"
                                href="?action=process&id=<?php
                                echo (int) $order["id"];
                                ?>"
                            >
                                Process
                            </a>

                            <a
                                class="complete-button"
                                href="?action=complete&id=<?php
                                echo (int) $order["id"];
                                ?>"
                            >
                                Complete
                            </a>

                            <a
                                class="reject-button"
                                href="?action=reject&id=<?php
                                echo (int) $order["id"];
                                ?>"
                            >
                                Reject
                            </a>

                            <a
                                class="delete-button"
                                href="?delete=<?php
                                echo (int) $order["id"];
                                ?>"
                                onclick="
                                    return confirm(
                                        'Delete this order permanently?'
                                    );
                                "
                            >
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td
                        colspan="7"
                        class="empty-message"
                    >
                        No orders found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>