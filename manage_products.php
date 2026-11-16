<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$error = $_SESSION['error'] ?? "";
$success = $_SESSION['success'] ?? "";
unset($_SESSION['error'], $_SESSION['success']);

// Handle Delete Listing
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    // Check ownership
    $chk = mysqli_query($conn, "SELECT product_id FROM products WHERE product_id = $delete_id AND user_id = $user_id");
    if ($chk && mysqli_num_rows($chk) > 0) {
        mysqli_query($conn, "DELETE FROM products WHERE product_id = $delete_id");
        $success = "Product deleted successfully.";
    } else {
        $error = "Unable to delete product or permission denied.";
    }
}

// Fetch all listings owned by user with their auction, bid, and order info
$sql = "SELECT p.product_id,
               p.title,
               p.category,
               p.price AS prod_price,
               p.description,
               a.auction_id,
               a.starting_price,
               a.auction_status,
               COALESCE(MAX(b.bid_amount), a.starting_price) AS current_bid,
               COUNT(b.bid_id) AS total_bids,
               ar.result_id,
               ar.result_status,
               ar.final_price,
               o.order_id,
               o.order_status,
               w.name AS winner_name
        FROM products p
        LEFT JOIN auctions a ON p.product_id = a.product_id
        LEFT JOIN bids b ON a.auction_id = b.auction_id
        LEFT JOIN auction_results ar ON a.auction_id = ar.auction_id
        LEFT JOIN orders o ON ar.result_id = o.result_id
        LEFT JOIN users w ON ar.winner_id = w.user_id
        WHERE p.user_id = $user_id
        GROUP BY p.product_id, a.auction_id
        ORDER BY p.product_id DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Listings - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="table-container">

        <div class="page-header">
            <h3>My Product Listings</h3>
            <a href="products.php" class="btn">+ Add New Product</a>
        </div>

        <?php if (!empty($success)) { ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>

        <?php if (!empty($error)) { ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Starting Price</th>
                    <th>Current Bid</th>
                    <th>Status</th>
                    <th>Result / Order</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($row['title']); ?></b></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td>$<?php echo number_format($row['starting_price'] ?? $row['prod_price'], 2); ?></td>
                        <td>
                            <b>$<?php echo number_format($row['current_bid'], 2); ?></b> (<?php echo $row['total_bids']; ?> bids)
                        </td>
                        <td><?php echo htmlspecialchars(ucfirst($row['auction_status'] ?? 'active')); ?></td>
                        <td>
                            <?php if ($row['result_status'] === 'sold') { ?>
                                Sold to <b><?php echo htmlspecialchars($row['winner_name']); ?></b> ($<?php echo number_format($row['final_price'], 2); ?>)
                                <?php if ($row['order_id']) { ?>
                                    <br><a href="order_details.php?order_id=<?php echo $row['order_id']; ?>">Order #<?php echo $row['order_id']; ?> (<?php echo htmlspecialchars($row['order_status']); ?>)</a>
                                <?php } ?>
                            <?php } elseif ($row['result_status'] === 'unsold') { ?>
                                Ended Unsold
                            <?php } else { ?>
                                In progress
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($row['auction_id']) { ?>
                                <a href="bid.php?auction_id=<?php echo $row['auction_id']; ?>">View</a> |
                            <?php } ?>

                            <?php if ($row['auction_status'] === 'active' || empty($row['auction_status'])) { ?>
                                <a href="products.php?edit=<?php echo $row['product_id']; ?>">Edit</a> |
                                <a href="end_auction.php?id=<?php echo $row['auction_id'] ?? $row['product_id']; ?>" onclick="return confirm('End this auction now?');">End Auction</a> |
                            <?php } ?>

                            <a href="manage_products.php?delete=<?php echo $row['product_id']; ?>" onclick="return confirm('Delete this listing?');" class="text-danger">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="muted text-center">
                You have not listed any products yet. <a href="products.php">Add one now!</a>
            </p>
        <?php } ?>

    </div>

</body>
</html>
