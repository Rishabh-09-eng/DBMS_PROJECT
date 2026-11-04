<?php
session_start();
include 'includes/db.php';

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch product details
$product_sql = "SELECT p.*, u.name AS seller_name 
                FROM products p 
                LEFT JOIN users u ON p.user_id = u.user_id 
                WHERE p.product_id = $product_id";
$product_res = mysqli_query($conn, $product_sql);
$product = mysqli_fetch_assoc($product_res);

if (!$product) {
    header("Location: auctions.php");
    exit();
}

$msg_success = "";
$msg_error = "";

// Fetch current highest bid
$bid_query = "SELECT b.bid_amount, b.bid_time, u.name AS bidder_name 
              FROM bids b 
              INNER JOIN users u ON b.user_id = u.user_id 
              WHERE b.product_id = $product_id 
              ORDER BY b.bid_amount DESC, b.bid_time ASC 
              LIMIT 1";
$bid_res = mysqli_query($conn, $bid_query);
$highest_bid = mysqli_fetch_assoc($bid_res);

$has_bids = ($highest_bid !== null);
$current_price = $has_bids ? floatval($highest_bid['bid_amount']) : floatval($product['price']);

// Process bid submission
if (isset($_POST['place_bid'])) {
    $user_id = intval($_SESSION['user_id']);
    $bid_amount = isset($_POST['bid_amount']) ? floatval($_POST['bid_amount']) : 0;

    if ($bid_amount <= 0) {
        $msg_error = "Please enter a valid bid amount.";
    } elseif ($bid_amount < floatval($product['price'])) {
        $msg_error = "Bid cannot be lower than the starting price of $" . number_format($product['price'], 2) . ".";
    } elseif ($has_bids && $bid_amount <= $current_price) {
        $msg_error = "Bid must be strictly higher than the current highest bid of $" . number_format($current_price, 2) . ".";
    } else {
        // Insert bid
        $insert_sql = "INSERT INTO bids (product_id, user_id, bid_amount) 
                       VALUES ($product_id, $user_id, $bid_amount)";
        $insert_res = mysqli_query($conn, $insert_sql);

        if ($insert_res) {
            $msg_success = "Bid of $" . number_format($bid_amount, 2) . " placed successfully!";

            // Refresh highest bid
            $bid_res = mysqli_query($conn, $bid_query);
            $highest_bid = mysqli_fetch_assoc($bid_res);
            $has_bids = true;
            $current_price = floatval($highest_bid['bid_amount']);
        } else {
            $msg_error = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch bid history
$history_sql = "SELECT b.bid_amount, b.bid_time, u.name AS bidder_name 
                FROM bids b 
                INNER JOIN users u ON b.user_id = u.user_id 
                WHERE b.product_id = $product_id 
                ORDER BY b.bid_amount DESC, b.bid_time ASC";
$history_res = mysqli_query($conn, $history_sql);
$total_bids = mysqli_num_rows($history_res);

// Minimum acceptable bid
$min_next_bid = $has_bids ? ($current_price + 1.00) : floatval($product['price']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bid on <?php echo htmlspecialchars($product['title']); ?> - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

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

    <div class="table-container" style="max-width: 750px;">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Auction Details</h3>
            <a href="auctions.php">&larr; Back to Auctions</a>
        </div>

        <?php if (!empty($msg_success)) { ?>
            <div class="alert-success"><?php echo $msg_success; ?></div>
        <?php } ?>

        <?php if (!empty($msg_error)) { ?>
            <div class="alert-error"><?php echo $msg_error; ?></div>
        <?php } ?>

        <!-- Product Information Table -->
        <table>
            <tr>
                <th style="width: 30%;">Product Title</th>
                <td><b><?php echo htmlspecialchars($product['title']); ?></b></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?php echo htmlspecialchars($product['category']); ?></td>
            </tr>
            <tr>
                <th>Description</th>
                <td><?php echo htmlspecialchars($product['description']); ?></td>
            </tr>
            <tr>
                <th>Starting Price</th>
                <td>$<?php echo number_format($product['price'], 2); ?></td>
            </tr>
            <tr>
                <th>Current Highest Bid</th>
                <td>
                    <b style="color: #16a34a; font-size: 16px;">
                        $<?php echo number_format($current_price, 2); ?>
                    </b>
                    <?php if ($has_bids) { ?>
                        (Leading Bidder: <b><?php echo htmlspecialchars($highest_bid['bidder_name']); ?></b>)
                    <?php } else { ?>
                        <span style="color: #666;">(No bids yet)</span>
                    <?php } ?>
                </td>
            </tr>
        </table>

        <!-- Place Bid Section -->
        <div style="margin-top: 25px; padding: 20px; background-color: #fafafa; border: 1px solid #ddd; border-radius: 6px;">
            <h3 style="margin-top: 0; text-align: left;">Place Your Bid</h3>

            <form action="bid.php?id=<?php echo $product_id; ?>" method="POST">
                <label style="margin-top: 0;">Enter Bid Amount ($)</label>
                <input 
                    type="number" 
                    step="0.01" 
                    min="<?php echo $min_next_bid; ?>" 
                    value="<?php echo number_format($min_next_bid, 2, '.', ''); ?>" 
                    name="bid_amount" 
                    required
                >
                <p style="text-align: left; font-size: 13px; color: #666; margin: 5px 0 0 0;">
                    Minimum required bid: <b>$<?php echo number_format($min_next_bid, 2); ?></b>
                </p>

                <button type="submit" name="place_bid" style="margin-top: 15px;">Submit Bid</button>
            </form>
        </div>

        <!-- Bidding History Table -->
        <div style="margin-top: 30px;">
            <h3 style="text-align: left; margin-bottom: 10px;">Bid History (<?php echo $total_bids; ?>)</h3>

            <?php if ($total_bids > 0) { ?>
                <table>
                    <tr>
                        <th style="width: 10%;">#</th>
                        <th>Bidder</th>
                        <th>Bid Amount</th>
                        <th>Bid Time</th>
                    </tr>
                    <?php 
                    $rank = 1;
                    while ($bid = mysqli_fetch_assoc($history_res)) { ?>
                        <tr>
                            <td><?php echo $rank++; ?></td>
                            <td><?php echo htmlspecialchars($bid['bidder_name']); ?></td>
                            <td><b>$<?php echo number_format($bid['bid_amount'], 2); ?></b></td>
                            <td><?php echo $bid['bid_time']; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } else { ?>
                <p style="text-align: left; color: #666; margin-top: 10px;">No bids have been placed yet.</p>
            <?php } ?>
        </div>

    </div>

</body>
</html>
