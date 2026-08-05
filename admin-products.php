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

$error = "";
$editingProduct = null;

$view = $_GET["view"] ?? "list";

if (!in_array($view, ["list", "add", "edit"], true)) {
    $view = "list";
}

if (isset($_GET["delete"])) {
    $productId = (int) $_GET["delete"];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM products WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $productId
    );

    mysqli_stmt_execute($stmt);

    header("Location: admin-products.php?view=list&deleted=1");
    exit();
}

if (isset($_GET["edit"])) {
    $view = "edit";
    $productId = (int) $_GET["edit"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM products WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $productId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $editingProduct = mysqli_fetch_assoc($result);

    if (!$editingProduct) {
        header("Location: admin-products.php?view=list");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productId = (int) ($_POST["product_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $price = (float) ($_POST["price"] ?? 0);
    $image = trim($_POST["image"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $featured = isset($_POST["featured"]) ? 1 : 0;

    if (
        $name === "" ||
        $category === "" ||
        $price <= 0 ||
        $image === ""
    ) {
        $error = "Please complete all required fields.";

        if ($productId > 0) {
            $view = "edit";
        } else {
            $view = "add";
        }
    } else {
        if ($productId > 0) {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE products
                 SET name = ?,
                     category = ?,
                     price = ?,
                     image = ?,
                     description = ?,
                     featured = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssdssii",
                $name,
                $category,
                $price,
                $image,
                $description,
                $featured,
                $productId
            );

            mysqli_stmt_execute($stmt);

            header("Location: admin-products.php?view=list&updated=1");
            exit();
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO products
                (
                    name,
                    category,
                    price,
                    image,
                    description,
                    featured
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssdssi",
                $name,
                $category,
                $price,
                $image,
                $description,
                $featured
            );

            mysqli_stmt_execute($stmt);

            header("Location: admin-products.php?view=list&added=1");
            exit();
        }
    }
}

$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $like = "%" . $search . "%";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM products
         WHERE name LIKE ?
            OR category LIKE ?
         ORDER BY id DESC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $like,
        $like
    );

    mysqli_stmt_execute($stmt);
    $products = mysqli_stmt_get_result($stmt);
} else {
    $products = mysqli_query(
        $conn,
        "SELECT * FROM products ORDER BY id DESC"
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
    <title>Manage Products | Happy Paws</title>
    <link
        rel="stylesheet"
        href="admin-products.css?v=20"
    >
    <link rel="stylesheet" href="admin-header.css?v=1">
</head>
<body>

<?php
if ($view === "add") {
    $pageTitle = "Add Product";
} elseif ($view === "edit") {
    $pageTitle = "Edit Product";
} else {
    $pageTitle = "Products";
}

$headerButtonText = "Go to Dashboard";
$headerButtonLink = "admin-dashboard.php";
include "admin-header.php";
?>

<nav class="product-navigation">
    <a
        href="admin-products.php?view=list"
        class="<?php echo $view === "list" ? "active" : ""; ?>"
    >
        All Products
    </a>

    <a
        href="admin-products.php?view=add"
        class="<?php echo $view === "add" ? "active" : ""; ?>"
    >
        Add Product
    </a>

    <a href="manage-orders.php">
        Orders
    </a>
</nav>

<main class="container">

    <?php if (isset($_GET["added"])): ?>
        <p class="success-message">
            Product added successfully.
        </p>
    <?php endif; ?>

    <?php if (isset($_GET["updated"])): ?>
        <p class="success-message">
            Product updated successfully.
        </p>
    <?php endif; ?>

    <?php if (isset($_GET["deleted"])): ?>
        <p class="success-message">
            Product deleted successfully.
        </p>
    <?php endif; ?>

    <?php if ($view === "add" || $view === "edit"): ?>

        <section
            class="product-form-section"
            id="product-form"
        >
            <h2>
                <?php
                echo $view === "edit"
                    ? "Edit Product"
                    : "Add Product";
                ?>
            </h2>

            <?php if ($error !== ""): ?>
                <p class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <input
                    type="hidden"
                    name="product_id"
                    value="<?php
                    echo (int) ($editingProduct["id"] ?? 0);
                    ?>"
                >

                <div class="form-grid">

                    <div class="form-group">
                        <label for="name">
                            Product Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            required
                            value="<?php
                            echo htmlspecialchars(
                                $editingProduct["name"] ??
                                $_POST["name"] ??
                                ""
                            );
                            ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="category">
                            Category
                        </label>

                        <input
                            type="text"
                            id="category"
                            name="category"
                            required
                            placeholder="Food & Treats, Toys, Grooming..."
                            value="<?php
                            echo htmlspecialchars(
                                $editingProduct["category"] ??
                                $_POST["category"] ??
                                ""
                            );
                            ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="price">
                            Price
                        </label>

                        <input
                            type="number"
                            id="price"
                            name="price"
                            min="1"
                            step="0.01"
                            required
                            value="<?php
                            echo htmlspecialchars(
                                $editingProduct["price"] ??
                                $_POST["price"] ??
                                ""
                            );
                            ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="image">
                            Image Path
                        </label>

                        <input
                            type="text"
                            id="image"
                            name="image"
                            required
                            placeholder="images/fish-food.jpg"
                            value="<?php
                            echo htmlspecialchars(
                                $editingProduct["image"] ??
                                $_POST["image"] ??
                                ""
                            );
                            ?>"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label for="description">
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                        ><?php
                        echo htmlspecialchars(
                            $editingProduct["description"] ??
                            $_POST["description"] ??
                            ""
                        );
                        ?></textarea>
                    </div>

                    <div class="checkbox-group full-width">
                        <input
                            type="checkbox"
                            id="featured"
                            name="featured"
                            <?php
                            $isFeatured =
                                (int) (
                                    $editingProduct["featured"] ??
                                    (isset($_POST["featured"]) ? 1 : 0)
                                );

                            if ($isFeatured === 1) {
                                echo "checked";
                            }
                            ?>
                        >

                        <label for="featured">
                            Feature on homepage
                        </label>
                    </div>

                </div>

                <div class="form-buttons">
                    <button type="submit">
                        <?php
                        echo $view === "edit"
                            ? "Update Product"
                            : "Add Product";
                        ?>
                    </button>

                    <a href="admin-products.php?view=list">
                        Cancel
                    </a>
                </div>
            </form>

        </section>

    <?php endif; ?>

    <?php if ($view === "list"): ?>

        <section class="product-list-section">

            <div class="list-heading">
                <h2>All Products</h2>

                <form
                    method="GET"
                    class="search-form"
                >
                    <input
                        type="hidden"
                        name="view"
                        value="list"
                    >

                    <input
                        type="text"
                        name="search"
                        placeholder="Search product or category"
                        value="<?php
                        echo htmlspecialchars($search);
                        ?>"
                    >

                    <button type="submit">
                        Search
                    </button>

                    <?php if ($search !== ""): ?>
                        <a href="admin-products.php?view=list">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php
                    if (
                        $products &&
                        mysqli_num_rows($products) > 0
                    ):
                    ?>

                        <?php
                        while (
                            $product =
                            mysqli_fetch_assoc($products)
                        ):
                        ?>

                            <tr>
                                <td>
                                    <img
                                        class="product-image"
                                        src="<?php
                                        echo htmlspecialchars(
                                            $product["image"]
                                        );
                                        ?>"
                                        alt="<?php
                                        echo htmlspecialchars(
                                            $product["name"]
                                        );
                                        ?>"
                                    >
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $product["name"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $product["category"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    Rs.
                                    <?php
                                    echo number_format(
                                        (float) $product["price"],
                                        2
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo (int) $product["featured"] === 1
                                        ? "Yes"
                                        : "No";
                                    ?>
                                </td>

                                <td class="action-buttons">
                                    <a
                                        class="edit-button"
                                        href="admin-products.php?edit=<?php
                                        echo (int) $product["id"];
                                        ?>"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        class="delete-button"
                                        href="admin-products.php?delete=<?php
                                        echo (int) $product["id"];
                                        ?>"
                                        onclick="
                                            return confirm(
                                                'Delete this product permanently?'
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
                                colspan="6"
                                class="empty-message"
                            >
                                No products found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

    <?php endif; ?>

</main>

</body>
</html>