<?php
include 'includes/db.php';

if (isset($_POST['add_product'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image = mysqli_real_escape_string($conn, $_POST['image']);

    $sql = "INSERT INTO products (title, category, price, description, image) 
            VALUES ('$title', '$category', '$price', '$description', '$image')";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        header("Location: manage_products.php");
        exit();
    } else {
        $error = "Failed to add product: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product - Online Auction</title>
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

        <h2>Add Product</h2>

        <?php if (!empty($error)) { ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>

        <form action="add_product.php" method="POST">

            <label>Product Title</label>
            <input type="text" name="title" required>

            <label>Product Category</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="Electronics">Electronics</option>
                <option value="Collectibles">Collectibles</option>
                <option value="Fashion">Fashion</option>
                <option value="Jewelry & Watches">Jewelry & Watches</option>
                <option value="Art & Antiques">Art & Antiques</option>
                <option value="Vehicles">Vehicles</option>
                <option value="Other">Other</option>
            </select>

            <label>Starting Price ($)</label>
            <input type="number" step="0.01" name="price" required>

            <label>Description</label>
            <textarea name="description" rows="3" required></textarea>

            <label>Image URL</label>
            <input type="text" name="image" placeholder="https://example.com/image.jpg">

            <button type="submit" name="add_product">Add Product</button>

        </form>

        <p><a href="manage_products.php">Back to Manage Products</a></p>

    </div>

</body>
</html>
