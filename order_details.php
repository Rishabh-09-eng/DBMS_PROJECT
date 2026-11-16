<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header("Location: orders.php");
    exit();
}

$success = $_SESSION['success'] ?? "";
$error = $_SESSION['error'] ?? "";
unset($_SESSION['success'], $_SESSION['error']);

// Fetch full order information
$sql = "SELECT o.*,
               ar.auction_id,
               ar.result_status,
               ar.final_price,
               a.starting_price,
               p.product_id,
               p.title AS product_title,
               p.category,
               p.description,
               p.image,
               p.user_id AS seller_id,
               u_seller.name AS seller_name,
               u_seller.email AS seller_email,
               u_buyer.name AS buyer_name,
               u_buyer.email AS buyer_email,
               u_buyer.phone AS buyer_phone,
               pay.payment_id,
               pay.amount AS payment_amount,
               pay.payment_method,
               pay.transaction_id,
               pay.payment_status,
               pay.payment_date,
               pay.bid_status,
               del.delivery_id,
               del.courier_name,
               del.tracking_number,
               del.delivery_status,
               del.shipped_at,
               del.delivered_at,
               addr.address_id,
               addr.address_line,
               addr.city,
               addr.state,
               addr.pincode,
               addr.country
        FROM orders o
        INNER JOIN auction_results ar ON o.result_id = ar.result_id
        INNER JOIN auctions a ON ar.auction_id = a.auction_id
        INNER JOIN products p ON a.product_id = p.product_id
        LEFT JOIN users u_seller ON p.user_id = u_seller.user_id
        LEFT JOIN users u_buyer ON o.buyer_id = u_buyer.user_id
        LEFT JOIN payments pay ON o.order_id = pay.order_id
        LEFT JOIN deliveries del ON o.order_id = del.order_id
        LEFT JOIN addresses addr ON del.address_id = addr.address_id
        WHERE o.order_id = $order_id";

$res = mysqli_query($conn, $sql);
$order = mysqli_fetch_assoc($res);

if (!$order) {
    header("Location: orders.php");
    exit();
}

$isBuyer = ($user_id == $order['buyer_id']);
$isSeller = ($user_id == $order['seller_id']);

if (!$isBuyer && !$isSeller) {
    die("Access denied. You are not associated with this order.");
}

// Handle Delivery Update by Seller
if (isset($_POST['update_delivery']) && $isSeller) {
    $courier = trim($_POST['courier_name']);
    $tracking = trim($_POST['tracking_number']);
    $new_status = trim($_POST['delivery_status']);

    $shipped_sql = "";
    $delivered_sql = "";
    $order_status_sql = "";

    if ($new_status === 'shipped') {
        $shipped_sql = ", shipped_at = IF(shipped_at IS NULL, NOW(), shipped_at)";
        $order_status_sql = "UPDATE orders SET order_status = 'shipped' WHERE order_id = $order_id";
    } elseif ($new_status === 'delivered') {
        $delivered_sql = ", delivered_at = IF(delivered_at IS NULL, NOW(), delivered_at)";
        $order_status_sql = "UPDATE orders SET order_status = 'completed' WHERE order_id = $order_id";
    }

    if (!empty($order['delivery_id'])) {
        $upd = "UPDATE deliveries 
                SET courier_name = ?, tracking_number = ?, delivery_status = ? $shipped_sql $delivered_sql 
                WHERE order_id = ?";
        $stmt = mysqli_prepare($conn, $upd);
        mysqli_stmt_bind_param($stmt, "sssi", $courier, $tracking, $new_status, $order_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!empty($order_status_sql)) {
            mysqli_query($conn, $order_status_sql);
        }

        $_SESSION['success'] = "Delivery information updated successfully.";
    } else {
        $_SESSION['error'] = "Delivery record does not exist yet.";
    }

    header("Location: order_details.php?order_id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order #<?php echo $order_id; ?> Details - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="table-container">

        <div class="page-header">
            <h3>Order #<?php echo $order_id; ?> Details</h3>
            <a href="orders.php">&larr; Back to Orders</a>
        </div>

        <?php if (!empty($success)) { ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>

        <?php if (!empty($error)) { ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <!-- Order & Product Information -->
        <h4>Order Summary</h4>
        <table>
            <tr>
                <th>Product</th>
                <td><b><?php echo htmlspecialchars($order['product_title']); ?></b></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?php echo htmlspecialchars($order['category']); ?></td>
            </tr>
            <tr>
                <th>Seller</th>
                <td><?php echo htmlspecialchars($order['seller_name'] ?? 'Seller'); ?> (<?php echo htmlspecialchars($order['seller_email'] ?? ''); ?>)</td>
            </tr>
            <tr>
                <th>Buyer</th>
                <td><?php echo htmlspecialchars($order['buyer_name']); ?> (<?php echo htmlspecialchars($order['buyer_email']); ?>)</td>
            </tr>
            <tr>
                <th>Order Date</th>
                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
            </tr>
            <tr>
                <th>Total Amount</th>
                <td><b>$<?php echo number_format($order['total_amount'], 2); ?></b></td>
            </tr>
            <tr>
                <th>Order Status</th>
                <td><b><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></b></td>
            </tr>
        </table>

        <!-- Payment Details -->
        <h4>Payment Details</h4>
        <?php if (!empty($order['payment_id'])) { ?>
            <table>
                <tr>
                    <th>Payment Method</th>
                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                </tr>
                <tr>
                    <th>Transaction ID</th>
                    <td><?php echo htmlspecialchars($order['transaction_id']); ?></td>
                </tr>
                <tr>
                    <th>Payment Amount</th>
                    <td>$<?php echo number_format($order['payment_amount'], 2); ?></td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td><?php echo htmlspecialchars($order['payment_date']); ?></td>
                </tr>
                <tr>
                    <th>Payment Status</th>
                    <td><b><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></b></td>
                </tr>
            </table>
        <?php } else { ?>
            <p class="muted">
                Payment has not been completed.
                <?php if ($isBuyer) { ?>
                    <a href="payment.php?order_id=<?php echo $order_id; ?>"><b>Click here to Pay Now</b></a>
                <?php } ?>
            </p>
        <?php } ?>

        <!-- Delivery & Shipping Details -->
        <h4>Delivery & Shipping Information</h4>
        <?php if (!empty($order['delivery_id'])) { ?>
            <table>
                <tr>
                    <th>Shipping Address</th>
                    <td>
                        <?php if (!empty($order['address_line'])) { ?>
                            <?php echo htmlspecialchars($order['address_line']); ?>,
                            <?php echo htmlspecialchars($order['city']); ?>,
                            <?php echo htmlspecialchars($order['state']); ?> -
                            <?php echo htmlspecialchars($order['pincode']); ?>,
                            <?php echo htmlspecialchars($order['country']); ?>
                        <?php } else { ?>
                            No address details recorded.
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <th>Courier</th>
                    <td><?php echo htmlspecialchars($order['courier_name'] ?? 'Pending Assignment'); ?></td>
                </tr>
                <tr>
                    <th>Tracking Number</th>
                    <td><?php echo htmlspecialchars($order['tracking_number'] ?? 'Pending'); ?></td>
                </tr>
                <tr>
                    <th>Delivery Status</th>
                    <td><b><?php echo htmlspecialchars(ucfirst($order['delivery_status'])); ?></b></td>
                </tr>
                <tr>
                    <th>Shipped Date</th>
                    <td><?php echo !empty($order['shipped_at']) ? htmlspecialchars($order['shipped_at']) : 'Not shipped yet'; ?></td>
                </tr>
                <tr>
                    <th>Delivered Date</th>
                    <td><?php echo !empty($order['delivered_at']) ? htmlspecialchars($order['delivered_at']) : 'In transit'; ?></td>
                </tr>
            </table>

            <!-- Update Delivery Form for Seller -->
            <?php if ($isSeller) { ?>
                <div class="card">
                    <h4>Update Shipping Tracking (Seller Action)</h4>
                    <form action="order_details.php?order_id=<?php echo $order_id; ?>" method="POST">
                        <label>Courier Name</label>
                        <input type="text" name="courier_name" value="<?php echo htmlspecialchars($order['courier_name'] ?? 'FedEx'); ?>" required>

                        <label>Tracking Number</label>
                        <input type="text" name="tracking_number" value="<?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?>" required>

                        <label>Delivery Status</label>
                        <select name="delivery_status" required>
                            <option value="pending" <?php echo ($order['delivery_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="shipped" <?php echo ($order['delivery_status'] === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="in_transit" <?php echo ($order['delivery_status'] === 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                            <option value="delivered" <?php echo ($order['delivery_status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                        </select>

                        <button type="submit" name="update_delivery">Update Delivery Status</button>
                    </form>
                </div>
            <?php } ?>

        <?php } else { ?>
            <p class="muted">Delivery record will be created after payment is completed.</p>
        <?php } ?>

    </div>

</body>
</html>
