<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$target_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($target_id <= 0) {
    header("Location: manage_products.php");
    exit();
}

mysqli_begin_transaction($conn);

try {
    // 1. Identify the auction and product, ensuring caller is the seller
    $checkSql = "SELECT a.auction_id, a.auction_status, p.product_id, p.user_id 
                 FROM auctions a 
                 INNER JOIN products p ON a.product_id = p.product_id 
                 WHERE (a.auction_id = ? OR p.product_id = ?) AND p.user_id = ? 
                 FOR UPDATE";
    $stmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmt, "iii", $target_id, $target_id, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $item = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$item) {
        throw new Exception("Auction not found or you are not authorized to end it.");
    }

    $auction_id = intval($item['auction_id']);
    $product_id = intval($item['product_id']);

    if ($item['auction_status'] !== 'active') {
        throw new Exception("This auction has already ended.");
    }

    // 2. Fetch the highest bid
    $bidSql = "SELECT bid_id, user_id, bid_amount 
               FROM bids 
               WHERE auction_id = ? 
               ORDER BY bid_amount DESC, bid_time ASC 
               LIMIT 1";
    $stmtBid = mysqli_prepare($conn, $bidSql);
    mysqli_stmt_bind_param($stmtBid, "i", $auction_id);
    mysqli_stmt_execute($stmtBid);
    $resBid = mysqli_stmt_get_result($stmtBid);
    $winningBid = mysqli_fetch_assoc($resBid);
    mysqli_stmt_close($stmtBid);

    if ($winningBid) {
        $winner_id = intval($winningBid['user_id']);
        $winning_bid_id = intval($winningBid['bid_id']);
        $final_price = floatval($winningBid['bid_amount']);
        $result_status = 'sold';
    } else {
        $winner_id = null;
        $winning_bid_id = null;
        $final_price = null;
        $result_status = 'unsold';
    }

    // 3. Mark auction and product as ended
    mysqli_query($conn, "UPDATE auctions SET auction_status = 'ended' WHERE auction_id = $auction_id");
    mysqli_query($conn, "UPDATE products SET status = 'Ended' WHERE product_id = $product_id");

    // 4. Insert into auction_results (ER: AUCTION produces AUCTION_RESULT)
    $stmtRes = mysqli_prepare($conn, "INSERT INTO auction_results (auction_id, winner_id, winning_bid_id, final_price, result_status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmtRes, "iiids", $auction_id, $winner_id, $winning_bid_id, $final_price, $result_status);
    if (!mysqli_stmt_execute($stmtRes)) {
        throw new Exception("Failed to record auction result: " . mysqli_stmt_error($stmtRes));
    }
    $result_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmtRes);

    // 5. If sold, automatically GENERATE ORDER (ER: AUCTION_RESULT generates ORDER)
    if ($result_status === 'sold' && $winner_id && $final_price) {
        $order_status = 'pending';
        $stmtOrder = mysqli_prepare($conn, "INSERT INTO orders (result_id, buyer_id, total_amount, order_status, order_date) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmtOrder, "iids", $result_id, $winner_id, $final_price, $order_status);
        if (!mysqli_stmt_execute($stmtOrder)) {
            throw new Exception("Failed to generate order for winning bidder: " . mysqli_stmt_error($stmtOrder));
        }
        mysqli_stmt_close($stmtOrder);
    }

    mysqli_commit($conn);
    $_SESSION['success'] = "Auction #$auction_id has been ended. Result recorded as '$result_status'.";
    header("Location: manage_products.php");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = $e->getMessage();
    header("Location: manage_products.php");
    exit();
}
