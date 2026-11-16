<?php
session_start();
include 'includes/db.php';

$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$searchFilter = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query joining auctions, products, users, and bids
$sql = "SELECT a.auction_id,
               a.starting_price,
               a.minimum_increment,
               a.start_time,
               a.end_time,
               a.auction_status,
               p.product_id,
               p.title,
               p.category,
               p.description,
               u.name AS seller_name,
               COALESCE(MAX(b.bid_amount), a.starting_price) AS current_price,
               COUNT(b.bid_id) AS total_bids
        FROM auctions a
        INNER JOIN products p ON a.product_id = p.product_id
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN bids b ON a.auction_id = b.auction_id
        WHERE a.auction_status = 'active'";

if (!empty($categoryFilter)) {
    $catSafe = mysqli_real_escape_string($conn, $categoryFilter);
    $sql .= " AND p.category = '$catSafe'";
}

if (!empty($searchFilter)) {
    $searchSafe = mysqli_real_escape_string($conn, $searchFilter);
    $sql .= " AND (p.title LIKE '%$searchSafe%' OR p.description LIKE '%$searchSafe%')";
}

$sql .= " GROUP BY a.auction_id ORDER BY a.auction_id DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Auctions - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="table-container">

        <div class="page-header">
            <h3>Active Auctions</h3>
            <?php if (isset($_SESSION['user_id'])) { ?>
                <a href="products.php" class="btn">+ Add Product</a>
            <?php } ?>
        </div>

        <form method="GET" action="auctions.php" class="filter-bar">
            <input type="text" name="search" placeholder="Search title or description..." value="<?php echo htmlspecialchars($searchFilter); ?>">
            <select name="category">
                <option value="">All Categories</option>
                <?php
                $cats = ['Electronics', 'Collectibles', 'Fashion', 'Jewelry & Watches', 'Art & Antiques', 'Vehicles', 'Home & Garden', 'Other'];
                foreach ($cats as $cat) {
                    $sel = ($categoryFilter === $cat) ? 'selected' : '';
                    echo "<option value=\"$cat\" $sel>$cat</option>";
                }
                ?>
            </select>
            <button type="submit">Filter</button>
            <?php if (!empty($searchFilter) || !empty($categoryFilter)) { ?>
                <a href="auctions.php">Reset</a>
            <?php } ?>
        </form>

        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Seller</th>
                    <th>Starting Price</th>
                    <th>Current Bid</th>
                    <th>Total Bids</th>
                    <th>Action</th>
                </tr>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td>
                            <b><a href="bid.php?auction_id=<?php echo $row['auction_id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></b>
                        </td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['seller_name'] ?? 'System'); ?></td>
                        <td>$<?php echo number_format($row['starting_price'], 2); ?></td>
                        <td><b>$<?php echo number_format($row['current_price'], 2); ?></b></td>
                        <td><?php echo $row['total_bids']; ?></td>
                        <td>
                            <a href="bid.php?auction_id=<?php echo $row['auction_id']; ?>"><b>Bid Now</b></a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="muted text-center">No active auctions found.</p>
        <?php } ?>

    </div>

</body>
</html>
