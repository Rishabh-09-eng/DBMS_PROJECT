<?php
session_start();
include 'includes/db.php';

$loggedIn = isset($_SESSION['user_id']);
$auctionLink = "auctions.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>BidZone - Online Auction System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <section class="hero">
        <h1>Discover. Bid. Win.</h1>
        <p>
            Welcome to BidZone, your online auction platform.
            Discover products, compete with other bidders
            and win exciting deals.
        </p>

        <div>
            <a href="auctions.php" class="btn">Explore Auctions</a>

            <?php if ($loggedIn) { ?>
                <a href="products.php" class="btn">List a Product  </a>
            <?php } else { ?>
                <a href="register.php" class="btn">Create Account</a>
            <?php } ?>
        </div>
    </section>

    <?php
    if (isset($_SESSION['success'])) {
        echo "<script>alert('" . htmlspecialchars($_SESSION['success'], ENT_QUOTES) . "');</script>";
        unset($_SESSION['success']);
    }
    ?>

</body>
</html>
