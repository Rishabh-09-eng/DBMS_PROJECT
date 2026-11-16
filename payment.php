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

// Fetch order and product details
$sql = "SELECT o.*,
               p.product_id,
               p.title AS product_title,
               p.category,
               p.description,
               p.image,
               u_seller.name AS seller_name
        FROM orders o
        INNER JOIN auction_results ar ON o.result_id = ar.result_id
        INNER JOIN auctions a ON ar.auction_id = a.auction_id
        INNER JOIN products p ON a.product_id = p.product_id
        LEFT JOIN users u_seller ON p.user_id = u_seller.user_id
        WHERE o.order_id = $order_id AND o.buyer_id = $user_id";

$res = mysqli_query($conn, $sql);
$order = mysqli_fetch_assoc($res);

if (!$order) {
    header("Location: orders.php");
    exit();
}

// If already paid, redirect to order_details
if ($order['order_status'] !== 'pending') {
    header("Location: order_details.php?order_id=" . $order_id);
    exit();
}

$error = "";

// Fetch user's existing addresses
$addrRes = mysqli_query($conn, "SELECT * FROM addresses WHERE user_id = $user_id ORDER BY address_id DESC");
$userAddresses = [];
while ($a = mysqli_fetch_assoc($addrRes)) {
    $userAddresses[] = $a;
}

// Process Payment Submission
if (isset($_POST['process_payment'])) {
    $payment_method = trim($_POST['payment_method'] ?? 'Credit Card');
    $address_choice = $_POST['address_choice'] ?? 'new';
    $address_id = 0;

    mysqli_begin_transaction($conn);

    try {
        // 1. Resolve delivery address
        if ($address_choice === 'existing' && !empty($_POST['selected_address_id'])) {
            $address_id = intval($_POST['selected_address_id']);
        } else {
            // Validate & save new address
            $addr_line = trim($_POST['address_line'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $pincode = trim($_POST['pincode'] ?? '');
            $country = trim($_POST['country'] ?? '') ?: 'USA';

            if (empty($addr_line) || empty($city) || empty($state) || empty($pincode)) {
                throw new Exception("Please provide all required delivery address details.");
            }

            $stmtAddr = mysqli_prepare($conn, "INSERT INTO addresses (user_id, address_line, city, state, pincode, country, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmtAddr, "isssss", $user_id, $addr_line, $city, $state, $pincode, $country);
            if (!mysqli_stmt_execute($stmtAddr)) {
                throw new Exception("Could not save delivery address.");
            }
            $address_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmtAddr);
        }

        if ($address_id <= 0) {
            throw new Exception("A valid delivery address is required.");
        }

        // 2. Insert into payments table (ER: ORDER has PAYMENT)
        $amount = floatval($order['total_amount']);
        $transaction_id = 'TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
        $payment_status = 'completed';
        $bid_status = 'paid';

        $stmtPay = mysqli_prepare($conn, "INSERT INTO payments (order_id, amount, payment_method, transaction_id, payment_status, payment_date, bid_status) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        mysqli_stmt_bind_param($stmtPay, "idssss", $order_id, $amount, $payment_method, $transaction_id, $payment_status, $bid_status);
        if (!mysqli_stmt_execute($stmtPay)) {
            throw new Exception("Payment processing failed: " . mysqli_stmt_error($stmtPay));
        }
        $payment_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtPay);

        // 3. Insert into deliveries table (ER: ORDER has DELIVERY, ADDRESS used for DELIVERY)
        $courier_name = 'FedEx Express';
        $tracking_number = 'TRK-' . strtoupper(bin2hex(random_bytes(4))) . rand(100, 999);
        $delivery_status = 'processing';

        $stmtDel = mysqli_prepare($conn, "INSERT INTO deliveries (order_id, address_id, courier_name, tracking_number, delivery_status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmtDel, "iisss", $order_id, $address_id, $courier_name, $tracking_number, $delivery_status);
        if (!mysqli_stmt_execute($stmtDel)) {
            throw new Exception("Delivery record initialization failed.");
        }
        $delivery_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmtDel);

        // 4. Update order status to 'paid'
        mysqli_query($conn, "UPDATE orders SET order_status = 'paid' WHERE order_id = $order_id");

        mysqli_commit($conn);
        $_SESSION['success'] = "Payment successful! Order #$order_id is now paid and queued for shipment.";
        header("Location: order_details.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout & Payment - Order #<?php echo $order_id; ?> - BidZone</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        function toggleAddress(type) {
            document.getElementById('existing_address_section').className = (type === 'existing') ? '' : 'hidden';
            document.getElementById('new_address_section').className = (type === 'new') ? '' : 'hidden';
        }
    </script>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="table-container">
        <div class="page-header">
            <h3>Checkout & Payment for Order #<?php echo $order_id; ?></h3>
            <a href="orders.php">&larr; Back to Orders</a>
        </div>

        <?php if (!empty($error)) { ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <!-- Order Summary Card -->
        <div class="card">
            <h4>Order Summary</h4>
            <p><b>Product:</b> <?php echo htmlspecialchars($order['product_title']); ?> (<?php echo htmlspecialchars($order['category']); ?>)</p>
            <p><b>Seller:</b> <?php echo htmlspecialchars($order['seller_name'] ?? 'Seller'); ?></p>
            <p><b>Total Amount to Pay:</b> <b>$<?php echo number_format($order['total_amount'], 2); ?></b></p>
        </div>

        <form action="payment.php?order_id=<?php echo $order_id; ?>" method="POST">

            <!-- Section 1: Delivery Address -->
            <div class="card">
                <h4>1. Delivery Address</h4>

                <?php if (!empty($userAddresses)) { ?>
                    <div>
                        <label>Address Option:</label>
                        <p>
                            <label class="inline-label">
                                <input type="radio" name="address_choice" value="existing" checked onclick="toggleAddress('existing')"> Use Saved Address
                            </label>
                            <label class="inline-label">
                                <input type="radio" name="address_choice" value="new" onclick="toggleAddress('new')"> Enter New Address
                            </label>
                        </p>
                    </div>

                    <div id="existing_address_section">
                        <label>Choose a Saved Address:</label>
                        <select name="selected_address_id">
                            <?php foreach ($userAddresses as $addr) { ?>
                                <option value="<?php echo $addr['address_id']; ?>">
                                    <?php echo htmlspecialchars($addr['address_line'] . ', ' . $addr['city'] . ', ' . $addr['state'] . ' ' . $addr['pincode'] . ' (' . $addr['country'] . ')'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php } else { ?>
                    <input type="hidden" name="address_choice" value="new">
                <?php } ?>

                <div id="new_address_section" class="<?php echo !empty($userAddresses) ? 'hidden' : ''; ?>">
                    <label>Street Address</label>
                    <input type="text" name="address_line" placeholder="123 Example St, Apt 4B">

                    <div class="form-row">
                        <div>
                            <label>City</label>
                            <input type="text" name="city" placeholder="New York">
                        </div>
                        <div>
                            <label>State / Province</label>
                            <input type="text" name="state" placeholder="NY">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>Postal Code</label>
                            <input type="text" name="pincode" placeholder="10001">
                        </div>
                        <div>
                            <label>Country</label>
                            <input type="text" name="country" value="United States">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Payment Details -->
            <div class="card">
                <h4>2. Payment Method</h4>

                <label>Select Payment Method</label>
                <select name="payment_method" required>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="UPI">UPI / Instant Pay</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>

            <!-- Pay button -->
            <button type="submit" name="process_payment">
                Confirm & Pay $<?php echo number_format($order['total_amount'], 2); ?>
            </button>
        </form>
    </div>

</body>
</html>
