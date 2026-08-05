<?php
$pageTitle = $pageTitle ?? "Admin Dashboard";
$headerButtonText = $headerButtonText ?? "Go to Dashboard";
$headerButtonLink = $headerButtonLink ?? "admin-dashboard.php";
?>

<header class="admin-main-header">
    <a href="dashboard.php?preview=1" class="admin-logo">
        <span class="logo-paws">🐾</span>
        <span class="logo-name">Happy Paws</span>
    </a>

    <div class="admin-header-center">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>

    <a
        href="<?php echo htmlspecialchars($headerButtonLink); ?>"
        class="header-action-button"
    >
        <?php echo htmlspecialchars($headerButtonText); ?>
    </a>
</header>
