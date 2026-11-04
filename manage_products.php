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

if (isset($_GET['delete'])) {

    $delete_id = intval($_GET['delete']);

    $sql = "DELETE FROM products WHERE product_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: manage_products.php");
        exit();
    } else {
        $error = "Failed to delete product.";
    }

    mysqli_stmt_close($stmt);
}

$sql = "SELECT * FROM products
        WHERE user_id = ?
        AND NOT EXISTS (
            SELECT 1
            FROM auction_results ar
            WHERE ar.product_id = products.product_id
        )
        ORDER BY product_id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
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
            <a href="auctions.php">Auctions</a>

            <a href="manage_products.php"
               style="color: #93c5fd; font-weight: bold;">
                Products
            </a>

            <span>
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>

            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="table-container">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">

            <h3 style="margin: 0;">My Product Listings</h3>

            <a href="products.php" class="hero-btn">
                + Add Product
            </a>

        </div>

        <?php if (!empty($error)) { ?>

            <div class="alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>

        <?php if (mysqli_num_rows($result) == 0) { ?>

            <p style="color: #666;">
                You haven't added any products yet.
                <a href="products.php">Add a product</a>
            </p>

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

                        <td>
                            <?php echo $row['product_id']; ?>
                        </td>

                        <td>
                            <?php if (!empty($row['image'])) { ?>

                                <img
                                    src="<?php echo htmlspecialchars($row['image']); ?>"
                                    width="50"
                                    height="40"
                                    style="object-fit: cover; border-radius: 4px;"
                                    alt="Product"
                                >

                            <?php } else { ?>

                                -

                            <?php } ?>
                        </td>

                        <td>
                            <b><?php echo htmlspecialchars($row['title']); ?></b>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['category']); ?>
                        </td>

                        <td>
                            $<?php echo htmlspecialchars($row['price']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['description']); ?>
                        </td>

                        <td>

                            <a href="bid.php?id=<?php echo $row['product_id']; ?>"
                               style="color: #16a34a; font-weight: bold;">
                                View Auction
                            </a>

                            |

                            <a href="products.php?edit=<?php echo $row['product_id']; ?>">
                                Edit
                            </a>

                            |

                            <a href="manage_products.php?delete=<?php echo $row['product_id']; ?>"
                               onclick="return confirm('Are you sure you want to delete this listing?');"
                               style="color: red;">
                                Delete
                            </a>

                            |

                            <a href="end_auction.php?id=<?php echo $row['product_id']; ?>"
                                onclick="return confirm('Are you sure you want to end this auction?');">
                                End Auction
                            </a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>
</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>