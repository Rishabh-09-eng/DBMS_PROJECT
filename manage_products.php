<?php
include 'includes/db.php';

// Delete product if delete requested
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $sql = "DELETE FROM products WHERE product_id = $delete_id";
    $del_res = mysqli_query($conn, $sql);
    if ($del_res) {
        header("Location: manage_products.php");
        exit();
    } else {
        $error = "Failed to delete: " . mysqli_error($conn);
    }
}

// Fetch all products from database
$sql = "SELECT * FROM products ORDER BY product_id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Listings - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <nav class="navbar">
        <h2>BidZone</h2>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <div class="table-container">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Product Listings</h3>
            <a href="add_product.php" class="hero-btn">+ Add Product</a>
        </div>

        <?php if (mysqli_num_rows($result) == 0) { ?>
            <p style="color: #666;">No products found in the database. <a href="add_product.php">Add a product</a></p>
        <?php } else { ?>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo $row['product_id']; ?></td>
                        <td>
                            <?php if (!empty($row['image'])) { ?>
                                <img src="<?php echo htmlspecialchars($row['image']); ?>" width="50" height="40" style="object-fit: cover;" alt="Product">
                            <?php } else { ?>
                                -
                            <?php } ?>
                        </td>
                        <td><b><?php echo htmlspecialchars($row['title']); ?></b></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td>$<?php echo htmlspecialchars($row['price']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['product_id']; ?>">Edit</a> | 
                            <a href="manage_products.php?delete=<?php echo $row['product_id']; ?>" onclick="return confirm('Are you sure you want to delete this listing?');" style="color: red;">Delete</a>
                        </td>
                    </tr>
                <?php } ?>

            </table>

        <?php } ?>

    </div>

</body>
</html>
