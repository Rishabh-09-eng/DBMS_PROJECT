<?php
session_start();

$loggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Online Auction System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="navbar">

        <h2>BidZone</h2>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="#">Browse Auctions</a>
            

            <?php if ($loggedIn) { ?>
                <a href="manage_products.php">Products</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php">Logout</a>

            <?php } else { ?>

                <a href="login.php">Login</a>
                <a href="register.php">Register</a>

            <?php } ?>

        </div>

    </nav>

    <section class="hero">

        <h1>Discover. Bid. Win.</h1>

        <p>
            Welcome to BidZone, your online auction platform.
            Discover products, compete with other bidders
            and win exciting deals.
        </p>

        <a href="#" class="hero-btn">Explore Auctions</a>

    </section>

    <?php
    if (isset($_SESSION['success'])) {
        echo "<script>alert('" . htmlspecialchars($_SESSION['success'], ENT_QUOTES) . "');</script>";
        unset($_SESSION['success']);
    }
    ?>

</body>
</html>