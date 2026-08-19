<?php
session_start();
require_once "db.php";
require_once "cart_helpers.php";

if(!isset($_SESSION["user_id"])){
    header("Location: signin.html");
    exit();
}

$isAdminPreview=($_SESSION["role"]??"")==="admin"&&isset($_GET["preview"]);

if(($_SESSION["role"]??"")==="admin"&&!$isAdminPreview){
    header("Location: admin-dashboard.php");
    exit();
}
$userId=(int)$_SESSION["user_id"];
$search=trim($_GET["search"]??"");
if(($_SESSION["role"]??"")==="admin"&&!$isAdminPreview){
    header("Location: admin-dashboard.php");
    exit();
}
$category=trim($_GET["category"]??"");
$page=max(1,(int)($_GET["page"]??1));
$productsPerPage=6;
$offset=($page-1)*$productsPerPage;

$where=" WHERE 1";
$params=[];
$types="";

if($search!==""){
    $where.=" AND (name LIKE ? OR category LIKE ? OR description LIKE ?)";
    $like="%".$search."%";
    $params=[$like,$like,$like];
    $types="sss";
}

if($category!==""){
    $where.=" AND category=?";
    $params[]=$category;
    $types.="s";
}

$countSql="SELECT COUNT(*) AS total FROM products".$where;
$countStmt=mysqli_prepare($conn,$countSql);

if(!empty($params)){
    mysqli_stmt_bind_param($countStmt,$types,...$params);
}

mysqli_stmt_execute($countStmt);
$countResult=mysqli_stmt_get_result($countStmt);
$totalProducts=(int)mysqli_fetch_assoc($countResult)["total"];
$totalPages=max(1,(int)ceil($totalProducts/$productsPerPage));

if($page>$totalPages){
    $page=$totalPages;
    $offset=($page-1)*$productsPerPage;
}

$sql="SELECT * FROM products".$where."
ORDER BY featured DESC,id DESC
LIMIT ".$productsPerPage." OFFSET ".$offset;

$stmt=mysqli_prepare($conn,$sql);

if(!empty($params)){
    mysqli_stmt_bind_param($stmt,$types,...$params);
}

mysqli_stmt_execute($stmt);
$products=mysqli_stmt_get_result($stmt);

$featuredResult=mysqli_query(
    $conn,
    "SELECT * FROM products
    WHERE featured=1
    ORDER BY id DESC
    LIMIT 6"
);

$categoriesResult=mysqli_query(
    $conn,
    "SELECT DISTINCT category
    FROM products
    ORDER BY category"
);
$wishlistIds=[];
if(!$isAdminPreview){
    $wishlistStmt=mysqli_prepare($conn,"SELECT product_id FROM wishlist WHERE user_id=?");
    mysqli_stmt_bind_param($wishlistStmt,"i",$userId);
    mysqli_stmt_execute($wishlistStmt);
    $wishlistResult=mysqli_stmt_get_result($wishlistStmt);
    while($wishlistRow=mysqli_fetch_assoc($wishlistResult)){
        $wishlistIds[]=(int)$wishlistRow["product_id"];
    }
}
$wishlistCount=count($wishlistIds);
$cartItemCount=$isAdminPreview?0:cartCount();

function pageUrl(int $page,string $search,string $category,bool $preview=false):string{
    $query=["page"=>$page];

    if($search!==""){
        $query["search"]=$search;
    }

    if($category!==""){
        $query["category"]=$category;
    }

    if($preview){
        $query["preview"]=1;
    }

    return "dashboard.php?".http_build_query($query)."#products";
}

function productCard(array $product,string $prefix="product",array $wishlistIds=[]):void{
    $id=(int)$product["id"];
    $inWishlist=in_array($id,$wishlistIds);
    $name=htmlspecialchars($product["name"]);
    $description=htmlspecialchars($product["description"]??"");
    $image=htmlspecialchars($product["image"]);
    $price=number_format((float)$product["price"],2);
?>
<article class="product-card">
    <?php if((int)$product["featured"]===1): ?>
        <span class="product-badge">Featured</span>
    <?php endif; ?>

    <div class="product-image">
        <img src="<?= $image ?>" alt="<?= $name ?>">
    </div>

    <div class="product-information">
        <h3><?= $name ?></h3>
        <p><?= $description ?></p>
        <div class="product-price">Rs. <?= $price ?></div>

        <label for="<?= $prefix ?>_quantity_<?= $id ?>">
            Quantity
        </label>
       <div class="product-actions">
       <input
        type="number"
        id="<?= $prefix ?>_quantity_<?= $id ?>"
        min="1"
        value="1">

       <?php if(($_SESSION["role"]??"")!=="admin"): ?>
       <button type="button" class="wishlist-button <?= $inWishlist?"active":"" ?>" data-product-id="<?= $id ?>" title="<?= $inWishlist?"Remove from wishlist":"Add to wishlist" ?>">
            <?= $inWishlist?"♥":"♡" ?>
       </button>
       <?php endif; ?>

    <button type="button" class="cart-button" data-product-id="<?= $id ?>" data-quantity-id="<?= $prefix ?>_quantity_<?= $id ?>">Add to Cart</button>
</div>
    </div>
</article>
<?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1.0"
    >
    <title>Happy Paws Pet Store</title>
    <link rel="stylesheet" href="dashboard.css?v=2">
</head>

<body>

<header class="main-header">
    <div class="top-header">

        <a
            href="dashboard.php<?= $isAdminPreview?"?preview=1":"" ?>"
            class="logo"
        >
            <span class="logo-icon">🐾</span>
            <span class="logo-text">Happy Paws</span>
        </a>

        <form
            class="search-box"
            method="get"
            action="dashboard.php"
        >
            <?php if($isAdminPreview): ?>
                <input type="hidden" name="preview" value="1">
            <?php endif; ?>

            <input
                name="search"
                type="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="Search food, toys, grooming products..."
            >

            <button type="submit">Search</button>
        </form>

        <nav class="account-navigation">
            <a href="#">
                Hi, <?= htmlspecialchars($_SESSION["name"]??"Customer") ?>
            </a>

            <a href="logout.php">Logout</a>
            <?php if(!$isAdminPreview): ?>
                <a href="wishlist_dashboard.php" class="wishlist-link">Wishlist (<span class="wishlist-count"><?= $wishlistCount ?></span>)</a>
            <?php endif; ?>
            <a href="<?= $isAdminPreview?'#':'cart.php' ?>" class="cart-link">Cart<?php if(!$isAdminPreview): ?> (<span class="cart-header-count"><?= $cartItemCount ?></span>)<?php endif; ?></a>
            <a href="<?= $isAdminPreview?'#':'checkout.php' ?>" class="checkout-link">Checkout</a>
        </nav>
    </div>

    <div class="bottom-header">
        <nav class="main-navigation">
            <a
                href="dashboard.php<?= $isAdminPreview?"?preview=1":"" ?>"
                class="active-link"
            >
                Home
            </a>

            <a href="#featured-products">Featured</a>
            <a href="#products">Products</a>
            <a href="#contact">Contact Us</a>
        </nav>
    </div>
</header>

<main class="page-layout">

    <aside class="category-sidebar">
        <h2>Categories</h2>

        <div class="category-list">
            <a href="dashboard.php<?= $isAdminPreview?"?preview=1":"" ?>">
                All Products
            </a>

            <?php while($cat=mysqli_fetch_assoc($categoriesResult)): ?>
                <?php
                $categoryQuery=[
                    "category"=>$cat["category"]
                ];

                if($isAdminPreview){
                    $categoryQuery["preview"]=1;
                }
                ?>

                <a href="dashboard.php?<?= http_build_query($categoryQuery) ?>">
                    <?= htmlspecialchars($cat["category"]) ?>
                </a>
            <?php endwhile; ?>
        </div>
    </aside>

    <div class="main-content">

        <section class="hero-section">
            <div class="hero-text">
                <span class="hero-label">
                    Everything your pet needs
                </span>

                <h1>
                    Quality products for happy and healthy pets
                </h1>

                <p>
                    Shop pet food, toys, grooming essentials,
                    accessories and more for your furry friends.
                </p>

                <a href="#products" class="shop-button">
                    Shop Now
                </a>
            </div>

            <div class="hero-visual">
                <img
                    src="images/hero-pets.png"
                    alt="Happy Pets"
                >
            </div>
        </section>

        <?php if($search===""&&$category===""): ?>
        <section
            class="product-section"
            id="featured-products"
        >
            <div class="section-heading">
                <div>
                    <span class="section-label">
                        Most popular
                    </span>

                    <h2>Featured Products</h2>
                </div>

                <a href="#products">
                    View all products
                </a>
            </div>

            <div class="product-grid">
                <?php if(
                    $featuredResult&&
                    mysqli_num_rows($featuredResult)>0
                ): ?>

                    <?php while(
                        $product=mysqli_fetch_assoc($featuredResult)
                    ): ?>
                        <?php productCard($product,"featured",$wishlistIds); ?>
                    <?php endwhile; ?>

                <?php else: ?>
                    <p>No featured products yet.</p>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <section class="product-section" id="products">

            <div class="section-heading">
                <div>
                    <span class="section-label">
                        <?= $search!==""?"Search results":"Explore our store" ?>
                    </span>

                    <h2>
                        <?= $category!==""?
                        htmlspecialchars($category):
                        "Products" ?>
                    </h2>
                </div>
            </div>

            <div class="product-grid">
                <?php if(
                    $products&&
                    mysqli_num_rows($products)>0
                ): ?>

                    <?php while(
                        $product=mysqli_fetch_assoc($products)
                    ): ?>
                        <?php productCard($product,"product",$wishlistIds); ?>
                    <?php endwhile; ?>

                <?php else: ?>
                    <p>No products found.</p>
                <?php endif; ?>
            </div>

            <?php if($totalPages>1): ?>
            <div class="pagination">

                <?php if($page>1): ?>
                    <a href="<?= htmlspecialchars(
                        pageUrl(
                            $page-1,
                            $search,
                            $category,
                            $isAdminPreview
                        )
                    ) ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <?php for($i=1;$i<=$totalPages;$i++): ?>
                    <a
                        href="<?= htmlspecialchars(
                            pageUrl(
                                $i,
                                $search,
                                $category,
                                $isAdminPreview
                            )
                        ) ?>"
                        class="<?= $i===$page?"active":"" ?>"
                    >
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if($page<$totalPages): ?>
                    <a href="<?= htmlspecialchars(
                        pageUrl(
                            $page+1,
                            $search,
                            $category,
                            $isAdminPreview
                        )
                    ) ?>">
                        Next
                    </a>
                <?php endif; ?>

            </div>
            <?php endif; ?>

        </section>

        <section class="contact-section" id="contact">

            <div class="contact-information">
                <span class="section-label">
                    Need assistance?
                </span>

                <h2>Contact Us</h2>

                <p>
                    Contact the Happy Paws team for product
                    information, delivery questions and order support.
                </p>

                <div class="contact-details">
                    <p>
                        <strong>Email:</strong>
                        happypaws@gmail.com
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        +977 98665690289
                    </p>

                    <p>
                        <strong>Address:</strong>
                        Kathmandu, Nepal
                    </p>
                </div>
            </div>

            <form class="contact-form">
                <label for="contact_name">
                    Your Name
                </label>

                <input
                    type="text"
                    id="contact_name"
                    placeholder="Enter your name"
                >

                <label for="contact_email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="contact_email"
                    placeholder="Enter your email"
                >

                <label for="contact_message">
                    Message
                </label>

                <textarea
                    id="contact_message"
                    rows="5"
                    placeholder="How can we help you?"
                ></textarea>

                <button type="button">
                    Send Message
                </button>
            </form>

        </section>
    </div>
</main>

<footer class="main-footer">
    <div class="footer-bottom">
        <p>
            &copy; 2026 Happy Paws Pet Store.
            All rights reserved.
        </p>
    </div>
</footer>


<script>
document.addEventListener("click",function(e){
    const cartButton=e.target.closest(".cart-button");
    if(cartButton){
        const quantityInput=document.getElementById(cartButton.dataset.quantityId);
        const quantity=Math.max(1,parseInt(quantityInput?.value||"1",10));
        const form=document.createElement("form");
        form.method="POST";
        form.action="cart_action.php";
        form.innerHTML='<input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="'+cartButton.dataset.productId+'"><input type="hidden" name="quantity" value="'+quantity+'"><input type="hidden" name="redirect" value="cart.php">';
        document.body.appendChild(form);
        form.submit();
        return;
    }

    const button=e.target.closest(".wishlist-button");
    if(!button)return;
    const productId=button.dataset.productId;
    button.disabled=true;
    fetch("wishlist.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"product_id="+encodeURIComponent(productId)
    })
    .then(response=>response.json())
    .then(data=>{
        if(!data.success){
            alert(data.message||"Wishlist could not be updated.");
            return;
        }
        if(data.inWishlist){
            button.textContent="♥";
            button.classList.add("active");
            button.title="Remove from wishlist";
        }else{
            button.textContent="♡";
            button.classList.remove("active");
            button.title="Add to wishlist";
        }
        document.querySelectorAll(".wishlist-button[data-product-id='"+productId+"']").forEach(btn=>{
            btn.textContent=data.inWishlist?"♥":"♡";
            btn.classList.toggle("active",data.inWishlist);
            btn.title=data.inWishlist?"Remove from wishlist":"Add to wishlist";
        });
        const count=document.querySelector(".wishlist-count");
        if(count)count.textContent=data.count;
    })
    .catch(()=>alert("Wishlist could not be updated."))
    .finally(()=>button.disabled=false);
});
</script>

</body>
</html>