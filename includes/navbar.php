<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<nav class="navbar">
    <h2><a href="index.php">BidZone</a></h2>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="auctions.php">Auctions</a>

        <?php if ($isLoggedIn) { ?>
            <a href="manage_products.php">Products</a>
            <a href="orders.php">My Orders</a>
            <a href="addresses.php">Addresses</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
            <a href="logout.php">Logout</a>
        <?php } else { ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php } ?>
    </div>
</nav>
