<?php
include 'includes/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Update product if form submitted
if (isset($_POST['edit_product'])) {
    $id = intval($_POST['id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);

    $sql = "UPDATE products SET 
            title='$title', 
            category='$category', 
            price='$price', 
            description='$description', 
            image='$image' 
            WHERE product_id=$id";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: manage_products.php");
        exit();
    } else {
        $error = "Failed to update product: " . mysqli_error($conn);
    }
}

// Fetch current product details from database
$sql = "SELECT * FROM products WHERE product_id=$id";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: manage_products.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body class="auth-page">

    <div class="container">

        <h2>Edit Listing</h2>

        <?php if (!empty($error)) { ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>

        <form action="edit_product.php" method="POST">

            <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">

            <label>Product Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>

            <label>Product Category</label>
            <select name="category" required>
                <?php
                $categories = ['Electronics', 'Collectibles', 'Fashion', 'Jewelry & Watches', 'Art & Antiques', 'Vehicles', 'Other'];
                foreach ($categories as $cat) {
                    $selected = ($product['category'] === $cat) ? 'selected' : '';
                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>

            <label>Starting Price ($)</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>

            <label>Description</label>
            <textarea name="description" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>

            <label>Image URL</label>
            <input type="text" name="image" value="<?php echo htmlspecialchars($product['image']); ?>">

            <button type="submit" name="edit_product">Update Listing</button>

        </form>

        <p><a href="manage_products.php">Back to Manage Products</a></p>

    </div>

</body>
</html>
