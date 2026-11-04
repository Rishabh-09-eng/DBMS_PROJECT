<?php
session_start();
include 'includes/db.php';

$loggedIn = isset($_SESSION['user_id']);

if (isset($_POST['add_product'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $_POST['image'];

    if ($loggedIn) {
        $user_id = intval($_SESSION['user_id']);
    } else {
        $user_id = "NULL";
    }

    $sql = "INSERT INTO products (user_id, title, category, price, description, image)
            VALUES ($user_id, '$title', '$category', '$price', '$description', '$image')";

    mysqli_query($conn, $sql);

    header("Location: manage_products.php");
    exit();
}

if (isset($_POST['update_product'])) {
    $id = intval($_POST['product_id']);
    $title = $_POST['title'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $_POST['image'];

    $sql = "UPDATE products SET
            title = '$title',
            category = '$category',
            price = '$price',
            description = '$description',
            image = '$image'
            WHERE product_id = $id";

    mysqli_query($conn, $sql);

    header("Location: products.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $sql = "DELETE FROM products WHERE product_id = $id";
    mysqli_query($conn, $sql);

    header("Location: products.php");
    exit();
}

$edit_product = null;

if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);

    $res = mysqli_query($conn, "SELECT * FROM products WHERE product_id = $id");
    $edit_product = mysqli_fetch_assoc($res);
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

            <?php if ($loggedIn) { ?>
                <a href="products.php">Products</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php">Logout</a>
            <?php } else { ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php } ?>
        </div>
    </nav>

    <div class="container" style="margin-top: 30px;">

        <h2>
            <?php
            if ($edit_product) {
                echo 'Edit Product';
            } else {
                echo 'Add New Product';
            }
            ?>
        </h2>

        <form action="products.php" method="POST">

            <?php if ($edit_product) { ?>
                <input type="hidden" name="product_id"
                       value="<?php echo $edit_product['product_id']; ?>">
            <?php } ?>

            <label>Product Name</label>
            <input type="text" name="title"
                   value="<?php
                   if ($edit_product) {
                       echo htmlspecialchars($edit_product['title']);
                   }
                   ?>"
                   required>

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
                    $selected = '';

                    if ($edit_product && $edit_product['category'] === $cat) {
                        $selected = 'selected';
                    }

                    echo "<option value=\"$cat\" $selected>$cat</option>";
                }
                ?>
            </select>

            <label>Starting Bid / Price ($)</label>
            <input type="number" step="0.01" name="price"
                   value="<?php
                   if ($edit_product) {
                       echo htmlspecialchars($edit_product['price']);
                   }
                   ?>"
                   required>

            <label>Description</label>
            <textarea name="description" rows="3" required><?php
                if ($edit_product) {
                    echo htmlspecialchars($edit_product['description']);
                }
            ?></textarea>

            <label>Image URL</label>
            <input type="text" name="image"
                   placeholder="https://example.com/item.jpg"
                   value="<?php
                   if ($edit_product) {
                       echo htmlspecialchars($edit_product['image']);
                   }
                   ?>">

            <?php if ($edit_product) { ?>
                <button type="submit" name="update_product">Update Product</button>
                <p><a href="products.php">Cancel Edit</a></p>
            <?php } else { ?>
                <button type="submit" name="add_product">Add Product</button>
            <?php } ?>

        </form>

        <p>
            <a href="manage_products.php">View Product Listings</a> |
            <a href="auctions.php">Browse Live Auctions</a>
        </p>

    </div>

</body>
</html>
