<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// 1. Fetch orders where user is buyer (won items)
$sqlBuyer = "SELECT o.order_id,
                    o.total_amount,
                    o.order_status,
                    o.order_date,
                    p.product_id,
                    p.title AS product_title,
                    u_seller.name AS seller_name,
                    del.delivery_id,
                    del.delivery_status,
                    del.tracking_number
             FROM orders o
             INNER JOIN auction_results ar ON o.result_id = ar.result_id
             INNER JOIN auctions a ON ar.auction_id = a.auction_id
             INNER JOIN products p ON a.product_id = p.product_id
             LEFT JOIN users u_seller ON p.user_id = u_seller.user_id
             LEFT JOIN deliveries del ON o.order_id = del.order_id
             WHERE o.buyer_id = $user_id
             ORDER BY o.order_id DESC";
$resBuyer = mysqli_query($conn, $sqlBuyer);

// 2. Fetch orders where user is seller (items sold to winners)
$sqlSeller = "SELECT o.order_id,
                     o.total_amount,
                     o.order_status,
                     o.order_date,
                     p.product_id,
                     p.title AS product_title,
                     u_buyer.name AS buyer_name,
                     del.delivery_id,
                     del.delivery_status
              FROM orders o
              INNER JOIN auction_results ar ON o.result_id = ar.result_id
              INNER JOIN auctions a ON ar.auction_id = a.auction_id
              INNER JOIN products p ON a.product_id = p.product_id
              LEFT JOIN users u_buyer ON o.buyer_id = u_buyer.user_id
              LEFT JOIN deliveries del ON o.order_id = del.order_id
              WHERE p.user_id = $user_id
              ORDER BY o.order_id DESC";
$resSeller = mysqli_query($conn, $sqlSeller);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="table-container">

        <div class="page-header">
            <h3>My Orders (Won Items)</h3>
            <a href="auctions.php" class="btn">Browse Auctions</a>
        </div>

        <?php if ($resBuyer && mysqli_num_rows($resBuyer) > 0) { ?>
            <table>
                <tr>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Seller</th>
                    <th>Total Amount</th>
                    <th>Order Date</th>
                    <th>Order Status</th>
                    <th>Delivery Status</th>
                    <th>Action</th>
                </tr>
                <?php while ($ord = mysqli_fetch_assoc($resBuyer)) { ?>
                    <tr>
                        <td><b>#<?php echo $ord['order_id']; ?></b></td>
                        <td><b><?php echo htmlspecialchars($ord['product_title']); ?></b></td>
                        <td><?php echo htmlspecialchars($ord['seller_name'] ?? 'Seller'); ?></td>
                        <td>$<?php echo number_format($ord['total_amount'], 2); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($ord['order_date'])); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($ord['order_status'])); ?></td>
                        <td>
                            <?php echo !empty($ord['delivery_status']) ? htmlspecialchars(ucfirst($ord['delivery_status'])) : 'Pending Payment'; ?>
                            <?php if (!empty($ord['tracking_number'])) { ?>
                                <br><small>Tracking: <?php echo htmlspecialchars($ord['tracking_number']); ?></small>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($ord['order_status'] === 'pending') { ?>
                                <a href="payment.php?order_id=<?php echo $ord['order_id']; ?>"><b>Pay Now</b></a>
                            <?php } else { ?>
                                <a href="order_details.php?order_id=<?php echo $ord['order_id']; ?>">View Details</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="muted text-center">You have not won any auctions yet.</p>
        <?php } ?>

        <!-- Seller orders section -->
        <?php if ($resSeller && mysqli_num_rows($resSeller) > 0) { ?>
            <h3>Orders for Items You Sold</h3>

            <table>
                <tr>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Buyer</th>
                    <th>Amount</th>
                    <th>Order Status</th>
                    <th>Delivery Status</th>
                    <th>Action</th>
                </tr>
                <?php while ($sOrd = mysqli_fetch_assoc($resSeller)) { ?>
                    <tr>
                        <td><b>#<?php echo $sOrd['order_id']; ?></b></td>
                        <td><b><?php echo htmlspecialchars($sOrd['product_title']); ?></b></td>
                        <td><?php echo htmlspecialchars($sOrd['buyer_name']); ?></td>
                        <td>$<?php echo number_format($sOrd['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($sOrd['order_status'])); ?></td>
                        <td>
                            <?php echo !empty($sOrd['delivery_status']) ? htmlspecialchars(ucfirst($sOrd['delivery_status'])) : 'Not Created'; ?>
                        </td>
                        <td>
                            <a href="order_details.php?order_id=<?php echo $sOrd['order_id']; ?>">Manage Delivery</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>

    </div>

</body>
</html>
