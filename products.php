<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$loggedIn = true;
$error = "";

if (isset($_POST['add_product'])) {

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);

    $sql = "INSERT INTO products
            (user_id, title, category, price, description, image)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "isssss",
        $user_id,
        $title,
        $category,
        $price,
        $description,
        $image
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_products.php");
        exit();
    } else {
        $error = "Failed to add product.";
    }

    mysqli_stmt_close($stmt);
}


if (isset($_POST['update_product'])) {

    $id = intval($_POST['product_id']);

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);

    $sql = "UPDATE products SET
            title = ?,
            category = ?,
            price = ?,
            description = ?,
            image = ?
            WHERE product_id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sssssii",
        $title,
        $category,
        $price,
        $description,
        $image,
        $id,
        $user_id
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_products.php");
        exit();
    } else {
        $error = "Failed to update product.";
    }

    mysqli_stmt_close($stmt);
}

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $sql = "DELETE FROM products
            WHERE product_id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    header("Location: manage_products.php");
    exit();
}

$edit_product = null;

if (isset($_GET['edit'])) {

    $id = intval($_GET['edit']);

    $sql = "SELECT * FROM products
            WHERE product_id = ? AND user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);

    mysqli_stmt_execute($stmt);

    $res = mysqli_stmt_get_result($stmt);

    $edit_product = mysqli_fetch_assoc($res);

    mysqli_stmt_close($stmt);

    if (!$edit_product) {
        header("Location: manage_products.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Products - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <nav class="navbar">

        <h2>BidZone</h2>

        <div class="nav-links">

            <a href="index.php">Home</a>
            <a href="auctions.php">Auctions</a>
            <a href="manage_products.php">Products</a>

            <span>
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>

            <a href="logout.php">Logout</a>

        </div>

    </nav>


    <div class="container" style="margin-top: 30px;">

        <h2>
            <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
        </h2>

        <?php if (!empty($error)) { ?>

            <div class="alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <form action="products.php<?php echo $edit_product ? '?edit=' . $edit_product['product_id'] : ''; ?>"
              method="POST">

            <?php if ($edit_product) { ?>

                <input type="hidden"
                       name="product_id"
                       value="<?php echo $edit_product['product_id']; ?>">

            <?php } ?>


            <label>Product Name</label>

            <input
                type="text"
                name="title"
                value="<?php echo htmlspecialchars($edit_product['title'] ?? ''); ?>"
                required
            >


            <label>Category</label>

            <select name="category" required>

                <option value="">Select Category</option>

                <?php

                $categories = [
                    'Electronics',
                    'Collectibles',
                    'Fashion',
                    'Jewelry & Watches',
                    'Art & Antiques',
                    'Vehicles',
                    'Other'
                ];

                foreach ($categories as $cat) {

                    $selected = (($edit_product['category'] ?? '') === $cat)
                                ? 'selected'
                                : '';

                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }

                ?>

            </select>


            <label>Starting Bid / Price ($)</label>

            <input
                type="number"
                step="0.01"
                name="price"
                value="<?php echo htmlspecialchars($edit_product['price'] ?? ''); ?>"
                required
            >


            <label>Description</label>

            <textarea name="description" rows="3" required><?php
                echo htmlspecialchars($edit_product['description'] ?? '');
            ?></textarea>


            <label>Image URL</label>

            <input
                type="text"
                name="image"
                placeholder="https://developers.elementor.com/docs/assets/img/elementor-placeholder-image.png"
                value="<?php echo htmlspecialchars($edit_product['image'] ?? ''); ?>"
            >


            <?php if ($edit_product) { ?>

                <button type="submit" name="update_product">
                    Update Product
                </button>

                <p>
                    <a href="products.php">Cancel Edit</a>
                </p>

            <?php } else { ?>

                <button type="submit" name="add_product">
                    Add Product
                </button>

            <?php } ?>

        </form>


        <p>
            <a href="manage_products.php">View Product Listings</a> |
            <a href="auctions.php">Browse Live Auctions</a>
        </p>

    </div>

</body>

</html>