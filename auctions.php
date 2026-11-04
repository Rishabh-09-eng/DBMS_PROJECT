<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$loggedIn = true;

$sql = "SELECT p.*, 
               COALESCE(MAX(b.bid_amount), p.price) AS current_price,
               COUNT(b.bid_id) AS total_bids
        FROM products p
        LEFT JOIN bids b ON p.product_id = b.product_id
        LEFT JOIN auction_results ar ON p.product_id = ar.product_id
        WHERE ar.result_id IS NULL
        GROUP BY p.product_id
        ORDER BY p.product_id DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auctions - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <h2>BidZone</h2>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="auctions.php">Auctions</a>
            <a href="manage_products.php">Products</a>
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="table-container">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Active Auctions</h3>
            <a href="add_product.php" class="hero-btn">+ Add Product</a>
        </div>

        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Starting Price</th>
                    <th>Current Highest Bid</th>
                    <th>Total Bids</th>
                    <th>Action</th>
                </tr>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($row['title']); ?></b></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>$<?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <b>$<?php echo number_format($row['current_price'], 2); ?></b>
                        </td>
                        <td><?php echo $row['total_bids']; ?></td>
                        <td>
                            <a href="bid.php?id=<?php echo $row['product_id']; ?>"><b>Bid Now</b></a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p style="color: #666; margin-top: 20px;">No active auctions found.</p>
        <?php } ?>

    </div>

</body>
</html>
