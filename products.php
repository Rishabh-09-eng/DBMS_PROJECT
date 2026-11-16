<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$error = "";
$success = "";

// Handle Add Product
if (isset($_POST['add_product'])) {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $min_increment = isset($_POST['minimum_increment']) && floatval($_POST['minimum_increment']) > 0 ? floatval($_POST['minimum_increment']) : 1.00;
    $duration_days = isset($_POST['duration_days']) ? intval($_POST['duration_days']) : 7;
    $end_time = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
    $description = trim($_POST['description']);
    $image = trim($_POST['image']);
    $status = 'Active';

    if (empty($title) || empty($category) || $price <= 0) {
        $error = "Please provide a valid title, category, and positive starting price.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Insert into products
            $stmt = mysqli_prepare($conn, "INSERT INTO products (user_id, title, category, price, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issdsss", $user_id, $title, $category, $price, $description, $image, $status);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to insert product: " . mysqli_stmt_error($stmt));
            }
            $product_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // 2. Insert into auctions
            $stmtAuc = mysqli_prepare($conn, "INSERT INTO auctions (product_id, starting_price, minimum_increment, start_time, end_time, auction_status) VALUES (?, ?, ?, NOW(), ?, 'active')");
            mysqli_stmt_bind_param($stmtAuc, "idds", $product_id, $price, $min_increment, $end_time);
            if (!mysqli_stmt_execute($stmtAuc)) {
                throw new Exception("Failed to create auction: " . mysqli_stmt_error($stmtAuc));
            }
            mysqli_stmt_close($stmtAuc);

            mysqli_commit($conn);
            $_SESSION['success'] = "Product and auction created successfully!";
            header("Location: manage_products.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Handle Update Product
if (isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id']);
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $min_increment = floatval($_POST['minimum_increment']);
    $description = trim($_POST['description']);
    $image = trim($_POST['image']);

    if (empty($title) || empty($category) || $price <= 0) {
        $error = "Please provide valid product details.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "UPDATE products SET title = ?, category = ?, price = ?, description = ?, image = ? WHERE product_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "ssdssii", $title, $category, $price, $description, $image, $product_id, $user_id);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to update product.");
            }
            mysqli_stmt_close($stmt);

            // Also update auction starting_price and minimum_increment if auction is still active
            $stmtAuc = mysqli_prepare($conn, "UPDATE auctions SET starting_price = ?, minimum_increment = ? WHERE product_id = ? AND auction_status = 'active'");
            mysqli_stmt_bind_param($stmtAuc, "ddi", $price, $min_increment, $product_id);
            mysqli_stmt_execute($stmtAuc);
            mysqli_stmt_close($stmtAuc);

            mysqli_commit($conn);
            $_SESSION['success'] = "Product updated successfully!";
            header("Location: manage_products.php");
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

// Handle Delete Product
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: manage_products.php");
    exit();
}

// Fetch product for editing if ?edit=
$edit_product = null;
$edit_auction = null;

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $edit_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $edit_product = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($edit_product) {
        $stmtAuc = mysqli_prepare($conn, "SELECT * FROM auctions WHERE product_id = ? ORDER BY auction_id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmtAuc, "i", $edit_id);
        mysqli_stmt_execute($stmtAuc);
        $resAuc = mysqli_stmt_get_result($stmtAuc);
        $edit_auction = mysqli_fetch_assoc($resAuc);
        mysqli_stmt_close($stmtAuc);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $edit_product ? 'Edit Product' : 'Add Product'; ?> - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="container wide">
        <h2><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>

        <?php if (!empty($error)) { ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form action="products.php" method="POST">
            <?php if ($edit_product) { ?>
                <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
            <?php } ?>

            <label>Product Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($edit_product['title'] ?? ''); ?>" required>

            <div class="form-row">
                <div>
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        $categories = ['Electronics', 'Collectibles', 'Fashion', 'Jewelry & Watches', 'Art & Antiques', 'Vehicles', 'Home & Garden', 'Other'];
                        foreach ($categories as $cat) {
                            $selected = ($edit_product && $edit_product['category'] === $cat) ? 'selected' : '';
                            echo "<option value=\"$cat\" $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label>Starting Price ($)</label>
                    <input type="number" step="0.01" min="0.01" name="price" value="<?php echo htmlspecialchars($edit_product['price'] ?? '10.00'); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label>Minimum Bid Increment ($)</label>
                    <input type="number" step="0.01" min="0.50" name="minimum_increment" value="<?php echo htmlspecialchars($edit_auction['minimum_increment'] ?? '5.00'); ?>" required>
                </div>

                <div>
                    <?php if (!$edit_product) { ?>
                        <label>Auction Duration</label>
                        <select name="duration_days">
                            <option value="1">1 Day</option>
                            <option value="3">3 Days</option>
                            <option value="7" selected>7 Days</option>
                            <option value="14">14 Days</option>
                            <option value="30">30 Days</option>
                        </select>
                    <?php } else { ?>
                        <label>Auction Status</label>
                        <input type="text" disabled value="<?php echo htmlspecialchars($edit_auction['auction_status'] ?? 'active'); ?>">
                    <?php } ?>
                </div>
            </div>

            <label>Description</label>
            <textarea name="description" rows="4" required><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>

            <label>Image URL (optional)</label>
            <input type="text" name="image" value="<?php echo htmlspecialchars($edit_product['image'] ?? ''); ?>">

            <?php if ($edit_product) { ?>
                <button type="submit" name="update_product">Update Product</button>
            <?php } else { ?>
                <button type="submit" name="add_product">Create Product</button>
            <?php } ?>
        </form>

        <p class="text-center"><a href="manage_products.php">Back to Products</a></p>
    </div>

</body>
</html>
