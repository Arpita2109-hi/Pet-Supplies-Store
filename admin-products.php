<?php
session_start();
require_once "db.php";
if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: signin.html");
    exit();
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "save";
    if ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $message = mysqli_stmt_execute($stmt) ? "Product deleted successfully." : "Could not delete product.";
    } else {
        $id = (int)($_POST["id"] ?? 0);
        $name = trim($_POST["name"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $price = (float)($_POST["price"] ?? 0);
        $image = trim($_POST["image"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $featured = isset($_POST["featured"]) ? 1 : 0;

        if ($name === "" || $category === "" || $price < 0 || $image === "") {
            $error = "Name, category, valid price and image path are required.";
        } elseif ($id > 0) {
            $stmt = mysqli_prepare($conn, "UPDATE products SET name=?, category=?, price=?, featured=?, description=?, image=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssdissi", $name, $category, $price, $featured, $description, $image, $id);
            $message = mysqli_stmt_execute($stmt) ? "Product updated successfully." : "Could not update product.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO products (name, category, price, featured, description, image) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssdiss", $name, $category, $price, $featured, $description, $image);
            $message = mysqli_stmt_execute($stmt) ? "Product added successfully." : "Could not add product.";
        }
    }
}

$editProduct = null;
if (isset($_GET["edit"])) {
    $editId = (int)$_GET["edit"];
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $editId);
    mysqli_stmt_execute($stmt);
    $editProduct = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$search = trim($_GET["search"] ?? "");
if ($search !== "") {
    $like = "%$search%";
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE name LIKE ? OR category LIKE ? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, "ss", $like, $like);
    mysqli_stmt_execute($stmt);
    $products = mysqli_stmt_get_result($stmt);
} else {
    $products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Products | Happy Paws Admin</title><link rel="stylesheet" href="admin-dashboard.css"></head><body>
<aside class="sidebar"><h2>🐾 Happy Paws</h2><p>Admin Panel</p><a href="admin-dashboard.php">Dashboard</a><a class="active" href="admin-products.php">Products</a><a href="#product-form">Add Product</a><a href="manage-orders.php">Orders</a><a href="dashboard.php?preview=1">View Storefront</a><a href="logout.php">Logout</a></aside>
<main class="content">
<div class="admin-top"><div><h1>Products</h1><p>All products below come directly from the MySQL database.</p></div><a class="primary-button" href="#product-form">+ Add Product</a></div>
<?php if ($message): ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form class="admin-search" method="get"><input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products by name or category..."><button>Search</button><?php if ($search): ?><a href="admin-products.php">Clear</a><?php endif; ?></form>
<div class="table-wrap"><table><thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Featured</th><th>Actions</th></tr></thead><tbody>
<?php while ($p = mysqli_fetch_assoc($products)): ?><tr><td><img class="thumb" src="<?= htmlspecialchars($p["image"]) ?>" alt=""></td><td><?= htmlspecialchars($p["name"]) ?></td><td><?= htmlspecialchars($p["category"]) ?></td><td>Rs. <?= number_format((float)$p["price"], 2) ?></td><td><?= (int)$p["featured"] ? "Yes" : "—" ?></td><td class="actions"><a href="admin-products.php?edit=<?= (int)$p["id"] ?>#product-form">Edit</a><form method="post" onsubmit="return confirm('Delete this product?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p["id"] ?>"><button class="delete-button">Delete</button></form></td></tr><?php endwhile; ?>
</tbody></table></div>

<section class="product-form-card" id="product-form"><div class="form-heading"><h2><?= $editProduct ? "Edit Product" : "Add Product" ?></h2><?php if ($editProduct): ?><a href="admin-products.php#product-form">Cancel edit</a><?php endif; ?></div>
<form method="post" class="product-form"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editProduct["id"] ?? 0) ?>">
<label>Name<input required name="name" value="<?= htmlspecialchars($editProduct["name"] ?? "") ?>"></label>
<label>Category<select required name="category"><?php $cats=["Food & Treats","Toys","Grooming","Health & Medicine","Accessories","Collars, Leashes & Harnesses","Beds & Furniture","Feeding Supplies","Travel Supplies","Clothing","Training Supplies","Cleaning & Hygiene","Cages & Habitats","Aquariums & Fish Supplies","Other"]; foreach($cats as $cat): ?><option <?= (($editProduct["category"] ?? "") === $cat) ? "selected" : "" ?>><?= htmlspecialchars($cat) ?></option><?php endforeach; ?></select></label>
<label>Price (Rs.)<input required min="0" step="0.01" type="number" name="price" value="<?= htmlspecialchars($editProduct["price"] ?? "") ?>"></label>
<label>Image path<input required name="image" placeholder="images/product-name.jpg" value="<?= htmlspecialchars($editProduct["image"] ?? "") ?>"><small>Put the image inside the images folder, then enter its path here.</small></label>
<label class="full">Description<textarea name="description" rows="4"><?= htmlspecialchars($editProduct["description"] ?? "") ?></textarea></label>
<label class="checkbox full"><input type="checkbox" name="featured" <?= !empty($editProduct["featured"]) ? "checked" : "" ?>> Feature on homepage</label>
<div class="full form-actions"><button class="primary-button" type="submit"><?= $editProduct ? "Update Product" : "Save Product" ?></button></div>
</form></section>
</main></body></html>
