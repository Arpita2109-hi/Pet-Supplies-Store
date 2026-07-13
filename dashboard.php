<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" >
    <title>Happy Paws Pet Store</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<header class="main-header">
    <div class="top-header">
        <a href="dashboard.php" class="logo">
            <span class="logo-icon">🐾</span>
            <span class="logo-text">
                Happy Paws
            </span>
        </a>
        <form class="search-box">
            <input type="search" placeholder="Search food, toys, grooming products..." >
            <button type="submit">
                Search
            </button>
        </form>
        <nav class="account-navigation">
            <a href="signin.html">
                Sign In
            </a>
            <a href="signup.html">
                Sign Up
            </a>
            <a href="#" class="cart-link">
                Cart
            </a>
            <a href="#" class="checkout-link">
                Checkout
            </a>
        </nav>
    </div>
    <div class="bottom-header">
        <nav class="main-navigation">
            <a href="dashboard.php" class="active-link">
                Home
            </a>
            <a href="#featured-products">
                Featured
            </a>
            <a href="#products">
                Products
            </a>
            <a href="#contact">
                Contact Us
            </a>
        </nav>
    </div>
</header>
<main class="page-layout">
    <aside class="category-sidebar">
            <h2>Categories</h2>
        <div class="category-list">
    <details>
        <summary>Food &amp; Treats</summary>
        <a href="#">Dog Food</a>
        <a href="#">Cat Food</a>
        <a href="#">Bird Food</a>
        <a href="#">Fish Food</a>
        <a href="#">Rabbit Food</a>
        <a href="#">Hamster Food</a>
        <a href="#">Reptile Food</a>
    </details>
    <a href="#">Toys</a>
    <a href="#">Grooming</a>
    <a href="#">Health &amp; Medicine</a>
    <a href="#">Accessories</a>
    <a href="#">Collars, Leashes &amp; Harnesses</a>
    <a href="#">Beds &amp; Furniture</a>
    <a href="#">Feeding Supplies</a>
    <a href="#">Travel Supplies</a>
    <details>
        <summary>Clothing</summary>
        <a href="#">Dog Clothing</a>
        <a href="#">Cat Clothing</a>
    </details>
    <a href="#">Training Supplies</a>
    <a href="#">Cleaning &amp; Hygiene</a>
    <details>
        <summary>Cages &amp; Habitats</summary>
        <a href="#">Bird Cages</a>
        <a href="#">Rabbit Cages</a>
        <a href="#">Dog Kennels</a>
        <a href="#">Cat Cages</a>
        <a href="#">Reptile Terrariums</a>
    </details>
    <a href="#">Aquariums &amp; Fish Supplies</a>
    <a href="#">Bird &amp; Reptile Supplies</a>
    <a href="#">New Arrivals</a>
    <a href="#">Sale &amp; Clearance</a>
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
            <img src="images/hero-pets.png" alt="Happy Pets">
            </div>
        </section>
        <section class="product-section" id="featured-products" >
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
                <article class="product-card">
                    <span class="product-badge">
                        Best Seller
                    </span>
                    <div class="product-image">
                        <img src="images/dog-food.jpg" alt="Premium dog food" >
                    </div>
                    <div class="product-information">
                        <h3>Pedigree Adult Dry Dog Food</h3>
                        <p>
                            Complete roasted chicken & vegetable dry food for adult dogs.
                            - 3kg pack
                        </p>
                        <div class="product-price">
                            Rs. 1,200
                        </div>
                        <label for="featured_quantity_1">
                            Quantity
                        </label>
                        <input type="number" id="featured_quantity_1" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
                <article class="product-card">
                    <span class="product-badge">
                        Most Sold
                    </span>
                    <div class="product-image">
                        <img src="images/cat-food.jpg" alt="Premium cat food" >
                    </div>
                    <div class="product-information">
                        <h3>Purina Friskies Adult Cat Food</h3>
                        <p>
                            Complete dry cat food with fish and seafood for adult cats.
                            - 2.7 kg pack
                        </p>
                        <div class="product-price">
                            Rs. 950
                        </div>
                        <label for="featured_quantity_2">
                            Quantity
                        </label>
                        <input type="number" id="featured_quantity_2" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
                <article class="product-card">
                    <span class="product-badge">
                        Popular
                    </span>
                    <div class="product-image">
                        <img src="images/pet-bed.jpg" alt="Comfortable pet bed" >
                    </div>
                    <div class="product-information">
                        <h3>Round Knitted Bed with Nonslip Base</h3>
                        <p>
                           Soft pet bed with a non-slip base. Available Brown, Cream, Grey, Pink, and Blue.
                        </p>
                        <div class="product-price">
                            Rs. 1,850
                        </div>
                        <label for="featured_quantity_3">
                            Quantity
                        </label>
                        <input type="number" id="featured_quantity_3" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
            </div>
        </section>
        <section class="product-section" id="products">
            <div class="section-heading">
                <div>
                    <span class="section-label">
                        Explore our store
                    </span>
                    <h2>Products</h2>
                </div>
            </div>
            <div class="product-grid">
                <article class="product-card">
                    <div class="product-image">
                        <img src="images/pet-shampoo.jpg" alt="Pet shampoo" >
                    </div>
                    <div class="product-information">
                        <h3>Ultra Mosturising Shampoo </h3>
                        <p>
                            Deeply moisturizes and cleans your pet's coat, leaving it soft, healthy, and fresh.
                        </p>
                        <div class="product-price">
                            Rs. 650
                        </div>
                        <label for="quantity_1">
                            Quantity
                        </label>
                        <input type="number" id="quantity_1" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image">
                        <img src="images/chew-toy.jpg" alt="Dog chew toy" >
                    </div>
                    <div class="product-information">
                        <h3>Corn-Shaped Dental Dog Chew Toy</h3>
                        <p>
                            It matches the picture perfectly and looks professional for an e-commerce pet store.
                        </p>
                        <div class="product-price">
                            Rs. 450
                        </div>
                        <label for="quantity_2">
                            Quantity
                        </label>
                        <input type="number" id="quantity_2" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image">
                        <img src="images/cat-toy.jpg" alt="Interactive cat toy" >
                    </div>
                    <div class="product-information">
                        <h3>Automatic Interactive Laser Cat Toy</h3>
                        <p>
                           Rechargeable laser toy with 3 play modes and 360° rotation to keep indoor cats active and entertained.
                        </p>
                        <div class="product-price">
                            Rs. 750
                        </div>
                        <label for="quantity_3">
                            Quantity
                        </label>
                        <input type="number" id="quantity_3" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image">
                        <img src="images/pet-brush.jpg" alt="Pet grooming brush" >
                    </div>
                    <div class="product-information">
                        <h3>3-in-1 Steam Pet Grooming Brush</h3>
                        <p>
                            Removes loose fur, detangles hair, and gently massages your pet. Suitable for both dogs and cats.
                        </p>
                        <div class="product-price">
                            Rs. 500
                        </div>
                        <label for="quantity_4">
                            Quantity
                        </label>
                        <input type="number" id="quantity_4" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image">
                        <img src="images/pet-collar.jpg" alt="Adjustable pet collar" >
                    </div>
                    <div class="product-information">
                        <h3>Adjustable Leather Pet Collar</h3>
                        <p>
                           Adjustable PU leather collar for dogs and cats. Available in S, M, L and multiple colors.
                        </p>
                        <div class="product-price">
                            Rs. 350
                        </div>
                        <label for="quantity_5">
                            Quantity
                        </label>
                        <input type="number" id="quantity_5" min="1" value="1" >
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
                <article class="product-card">
                    <div class="product-image">
                        <img  src="images/fish-food.jpg"  alt="Fish food">
                    </div>
                    <div class="product-information">
                        <h3>Wardley Pond Fish Food Stix - 1.36kg</h3>
                        <p>
                            Complete daily food for healthy aquarium
                            fish.
                        </p>
                        <div class="product-price">
                            Rs. 300
                        </div>
                        <label for="quantity_6">
                            Quantity
                        </label>
                        <input  type="number" id="quantity_6" min="1" value="1">
                        <button type="button">
                            Add to Cart
                        </button>
                    </div>
                </article>
            </div>
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
                <input type="text" id="contact_name" placeholder="Enter your name">
                <label for="contact_email">
                    Email Address
                </label>
                <input type="email" id="contact_email" placeholder="Enter your email">
                <label for="contact_message">
                    Message
                </label>
                <textarea id="contact_message" rows="5" placeholder="How can we help you?" ></textarea>
                <button type="button">
                    Send Message
                </button>
            </form>
        </section>
    </div>
</main>
<footer class="main-footer">
    <div class="footer-grid">
        <div class="footer-about">
            <h2>🐾 Happy Paws</h2>
            <p>
                Your trusted online store for quality pet food,
                toys, grooming products and accessories.
            </p>
        </div>
        <div>
            <h3>Quick Links</h3>
            <ul>
                <li>
                    <a href="dashboard.php">Home</a>
                </li>
                <li>
                    <a href="#featured-products">Featured</a>
                </li>
                <li>
                    <a href="#products">Products</a>
                </li>
                <li>
                    <a href="#contact">Contact</a>
                </li>
            </ul>
        </div>
        <div>
            <h3>Customer Service</h3>
            <ul>
                <li>
                    <a>Delivery Information</a>
                </li>
                <li>
                    <a>Returns and Refunds</a>
                </li>
                <li>
                    <a>Frequently Asked Questions</a>
                </li>
                <li>
                    <a>Privacy Policy</a>
                </li>
            </ul>
        </div>
        <div>
            <h3>Contact</h3>
            <p>happypaws@gmail.com</p>
            <p>+977 98665690289</p>
            <p>Kathmandu, Nepal</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>
            &copy; 2026 Happy Paws Pet Store. All rights reserved.
        </p>
    </div>
</footer>
</body>
</html>