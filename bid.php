<?php
session_start();
include 'includes/db.php';

// Accept auction_id or fallback id (can be auction_id or product_id)
$auction_id = 0;
if (isset($_GET['auction_id'])) {
    $auction_id = intval($_GET['auction_id']);
} elseif (isset($_GET['id'])) {
    $param_id = intval($_GET['id']);
    // Check if param_id matches an auction_id directly
    $chk = mysqli_query($conn, "SELECT auction_id FROM auctions WHERE auction_id = $param_id");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $auction_id = $param_id;
    } else {
        // Fallback: check if param_id matches a product_id
        $chk2 = mysqli_query($conn, "SELECT auction_id FROM auctions WHERE product_id = $param_id ORDER BY auction_id DESC LIMIT 1");
        if ($chk2 && ($row2 = mysqli_fetch_assoc($chk2))) {
            $auction_id = intval($row2['auction_id']);
        }
    }
}

if ($auction_id <= 0) {
    header("Location: auctions.php");
    exit();
}

// Fetch auction and product details
$sql = "SELECT a.*,
               p.product_id,
               p.title,
               p.category,
               p.description,
               p.image,
               p.user_id AS seller_id,
               u.name AS seller_name
        FROM auctions a
        INNER JOIN products p ON a.product_id = p.product_id
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE a.auction_id = $auction_id";

$res = mysqli_query($conn, $sql);
$auction = mysqli_fetch_assoc($res);

if (!$auction) {
    header("Location: auctions.php");
    exit();
}

$product_id = $auction['product_id'];
$isSeller = isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $auction['seller_id']);
$msg_success = "";
$msg_error = "";

// Check highest bid currently
$bid_query = "SELECT b.bid_id, b.bid_amount, b.bid_time, b.user_id, u.name AS bidder_name 
              FROM bids b 
              INNER JOIN users u ON b.user_id = u.user_id 
              WHERE b.auction_id = $auction_id 
              ORDER BY b.bid_amount DESC, b.bid_time ASC 
              LIMIT 1";
$bid_res = mysqli_query($conn, $bid_query);
$highest_bid = mysqli_fetch_assoc($bid_res);

$has_bids = ($highest_bid !== null);
$current_price = $has_bids ? floatval($highest_bid['bid_amount']) : floatval($auction['starting_price']);
$min_increment = floatval($auction['minimum_increment']) > 0 ? floatval($auction['minimum_increment']) : 1.00;

// Calculate minimum required next bid
if ($has_bids) {
    $min_next_bid = $current_price + $min_increment;
} else {
    $min_next_bid = floatval($auction['starting_price']);
}

// Process bid submission
if (isset($_POST['place_bid'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $bidder_id = intval($_SESSION['user_id']);
    $bid_amount = isset($_POST['bid_amount']) ? floatval($_POST['bid_amount']) : 0;

    if ($auction['auction_status'] !== 'active') {
        $msg_error = "This auction is closed.";
    } elseif ($bidder_id == $auction['seller_id']) {
        $msg_error = "You cannot bid on your own listing.";
    } elseif ($bid_amount < $min_next_bid) {
        $msg_error = "Your bid must be at least $" . number_format($min_next_bid, 2) . ".";
    } else {
        // Insert into bids table with both auction_id and product_id
        $stmt = mysqli_prepare($conn, "INSERT INTO bids (auction_id, product_id, user_id, bid_amount, bid_time) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "iiid", $auction_id, $product_id, $bidder_id, $bid_amount);

        if (mysqli_stmt_execute($stmt)) {
            $msg_success = "Your bid of $" . number_format($bid_amount, 2) . " was placed successfully!";

            // Refresh highest bid info
            $bid_res = mysqli_query($conn, $bid_query);
            $highest_bid = mysqli_fetch_assoc($bid_res);
            $has_bids = true;
            $current_price = floatval($highest_bid['bid_amount']);
            $min_next_bid = $current_price + $min_increment;
        } else {
            $msg_error = "Failed to place bid: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all bids for bid history table
$history_sql = "SELECT b.bid_amount, b.bid_time, u.name AS bidder_name 
                FROM bids b 
                INNER JOIN users u ON b.user_id = u.user_id 
                WHERE b.auction_id = $auction_id 
                ORDER BY b.bid_amount DESC, b.bid_time ASC";
$history_res = mysqli_query($conn, $history_sql);
$total_bids = mysqli_num_rows($history_res);

// Fetch auction result if ended
$auction_result = null;
if ($auction['auction_status'] !== 'active') {
    $resSql = "SELECT ar.*, u.name AS winner_name 
               FROM auction_results ar 
               LEFT JOIN users u ON ar.winner_id = u.user_id 
               WHERE ar.auction_id = $auction_id";
    $rRes = mysqli_query($conn, $resSql);
    $auction_result = mysqli_fetch_assoc($rRes);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($auction['title']); ?> - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="table-container">

        <div class="page-header">
            <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
            <a href="auctions.php">&larr; Back to Auctions</a>
        </div>

        <?php if (!empty($msg_success)) { ?>
            <div class="alert-success"><?php echo htmlspecialchars($msg_success); ?></div>
        <?php } ?>

        <?php if (!empty($msg_error)) { ?>
            <div class="alert-error"><?php echo htmlspecialchars($msg_error); ?></div>
        <?php } ?>

        <?php if ($auction['auction_status'] !== 'active') { ?>
            <div class="alert-info">
                <b>Auction Ended.</b>
                <?php if ($auction_result && $auction_result['result_status'] === 'sold') { ?>
                    Sold to <b><?php echo htmlspecialchars($auction_result['winner_name']); ?></b> for <b>$<?php echo number_format($auction_result['final_price'], 2); ?></b>.
                <?php } else { ?>
                    Item ended without winning bids.
                <?php } ?>
            </div>
        <?php } ?>

        <p><?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>

        <table>
            <tr>
                <th>Category</th>
                <td><?php echo htmlspecialchars($auction['category']); ?></td>
            </tr>
            <tr>
                <th>Seller</th>
                <td><?php echo htmlspecialchars($auction['seller_name'] ?? 'System User'); ?></td>
            </tr>
            <tr>
                <th>Starting Price</th>
                <td>$<?php echo number_format($auction['starting_price'], 2); ?></td>
            </tr>
            <tr>
                <th>Minimum Increment</th>
                <td>+$<?php echo number_format($min_increment, 2); ?></td>
            </tr>
            <tr>
                <th>Current Highest Bid</th>
                <td>
                    <b>$<?php echo number_format($current_price, 2); ?></b>
                    <?php if ($has_bids) { ?>
                        (by <?php echo htmlspecialchars($highest_bid['bidder_name']); ?>)
                    <?php } else { ?>
                        (No bids yet)
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th>Total Bids</th>
                <td><?php echo $total_bids; ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td><?php echo htmlspecialchars(ucfirst($auction['auction_status'])); ?></td>
            </tr>
        </table>

        <!-- Bidding section -->
        <?php if ($auction['auction_status'] === 'active') { ?>
            <?php if (!isset($_SESSION['user_id'])) { ?>
                <p>You must <a href="login.php">login</a> to place a bid.</p>
            <?php } elseif ($isSeller) { ?>
                <div class="alert-info">
                    You are the seller of this item. You can end this auction from <a href="manage_products.php">Products</a>.
                </div>
            <?php } else { ?>
                <div class="card">
                    <h4>Place a Bid</h4>
                    <form action="bid.php?auction_id=<?php echo $auction_id; ?>" method="POST" class="filter-bar">
                        <input type="number" step="0.01" min="<?php echo $min_next_bid; ?>" value="<?php echo number_format($min_next_bid, 2, '.', ''); ?>" name="bid_amount" required>
                        <button type="submit" name="place_bid">Submit Bid</button>
                    </form>
                    <p class="muted">Minimum next bid: <b>$<?php echo number_format($min_next_bid, 2); ?></b></p>
                </div>
            <?php } ?>
        <?php } ?>

        <!-- Bid history table -->
        <h4>Bid History (<?php echo $total_bids; ?>)</h4>
        <?php if ($total_bids > 0) { ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Bidder</th>
                    <th>Bid Amount</th>
                    <th>Bid Time</th>
                </tr>
                <?php 
                $rank = 1;
                while ($b = mysqli_fetch_assoc($history_res)) { ?>
                    <tr>
                        <td><?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars($b['bidder_name']); ?></td>
                        <td><b>$<?php echo number_format($b['bid_amount'], 2); ?></b></td>
                        <td><?php echo htmlspecialchars($b['bid_time']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="muted">No bids placed yet.</p>
        <?php } ?>

    </div>

</body>
</html>
