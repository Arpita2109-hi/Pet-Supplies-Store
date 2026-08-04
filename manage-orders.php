<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: signin.html");
    exit();
}

/* Update order status */
if (isset($_GET["action"], $_GET["id"])) {
    $id = (int) $_GET["id"];
    $action = $_GET["action"];

    $allowedStatuses = [
        "process" => "processing",
        "complete" => "completed",
        "reject" => "rejected"
    ];

    if (isset($allowedStatuses[$action])) {
        $status = $allowedStatuses[$action];

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders SET status = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "si", $status, $id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: manage-orders.php");
    exit();
}

/* Delete order */
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM orders WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    header("Location: manage-orders.php");
    exit();
}

/* Search orders */
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $like = "%" . $search . "%";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM orders
         WHERE customer_name LIKE ?
            OR customer_email LIKE ?
            OR status LIKE ?
            OR CAST(id AS CHAR) LIKE ?
         ORDER BY created_at DESC"
    );

    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);
} else {
    $orders = mysqli_query(
        $conn,
        "SELECT * FROM orders ORDER BY created_at DESC"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders | Happy Paws</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: "Times New Roman", Times, serif;
        }

        body {
            margin: 0;
            background: #fff8f3;
            color: #2f241f;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 45px;
            background: white;
            border-bottom: 1px solid #efd8cc;
        }

        .header h1 {
            margin: 0;
            color: #e95d3f;
        }

        .back-button {
            padding: 11px 18px;
            border-radius: 8px;
            background: #e95d3f;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            width: 94%;
            margin: 35px auto;
        }

        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form input {
            width: 310px;
            padding: 12px;
            border: 1px solid #e5c9bb;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-form button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            background: #e95d3f;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .table-wrapper {
            overflow-x: auto;
            border: 1px solid #ead5ca;
            border-radius: 14px;
            background: white;
            box-shadow: 0 10px 25px rgba(80, 50, 35, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0e2da;
        }

        th {
            background: #ffe7da;
            color: #713c2c;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .pending {
            background: #fff0c7;
            color: #8a6700;
        }

        .processing {
            background: #dcecff;
            color: #225b94;
        }

        .completed {
            background: #dff4df;
            color: #28702d;
        }

        .rejected {
            background: #ffe0df;
            color: #a52d27;
        }

        .action {
            display: inline-block;
            margin: 3px;
            padding: 7px 10px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .process {
            background: #448ac7;
        }

        .complete {
            background: #3e9a50;
        }

        .reject {
            background: #d95c4c;
        }

        .delete {
            background: #7a6b64;
        }

        .empty {
            padding: 35px;
            text-align: center;
            color: #7a6b64;
        }
    </style>
</head>

<body>

<header class="header">
    <h1>Happy Paws — Manage Orders</h1>

    <a class="back-button" href="admin-dashboard.php">
        Back to Dashboard
    </a>
</header>

<main class="container">

    <div class="top-section">
        <h2>Customer Orders</h2>

        <form class="search-form" method="GET">
            <input
                type="text"
                name="search"
                placeholder="Search order, customer, email or status"
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit">Search</button>
        </form>
    </div>

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
            <?php if ($orders && mysqli_num_rows($orders) > 0): ?>

                <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td>#<?php echo $order["id"]; ?></td>

                        <td>
                            <?php echo htmlspecialchars($order["customer_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($order["customer_email"]); ?>
                        </td>

                        <td>
                            Rs. <?php echo number_format((float)$order["total"], 2); ?>
                        </td>

                        <td>
                            <span class="status <?php echo $order["status"]; ?>">
                                <?php echo htmlspecialchars($order["status"]); ?>
                            </span>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($order["created_at"]); ?>
                        </td>

                        <td>
                            <a
                                class="action process"
                                href="?action=process&id=<?php echo $order["id"]; ?>"
                            >
                                Process
                            </a>

                            <a
                                class="action complete"
                                href="?action=complete&id=<?php echo $order["id"]; ?>"
                            >
                                Complete
                            </a>

                            <a
                                class="action reject"
                                href="?action=reject&id=<?php echo $order["id"]; ?>"
                            >
                                Reject
                            </a>

                            <a
                                class="action delete"
                                href="?delete=<?php echo $order["id"]; ?>"
                                onclick="return confirm('Delete this order?');"
                            >
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7" class="empty">
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