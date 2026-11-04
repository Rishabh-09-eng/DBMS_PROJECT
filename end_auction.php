<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header("Location: manage_products.php");
    exit();
}

mysqli_begin_transaction($conn);

try {

    $sql = "SELECT product_id
            FROM products
            WHERE product_id = ? AND user_id = ?
            FOR UPDATE";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $product_id, $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$product) {
        throw new Exception("Product not found or you are not its owner.");
    }

    $sql = "SELECT result_id
            FROM auction_results
            WHERE product_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $existing_result = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($existing_result) {
        throw new Exception("This auction has already ended.");
    }

    $sql = "SELECT bid_id, user_id, bid_amount
            FROM bids
            WHERE product_id = ?
            ORDER BY bid_amount DESC, bid_time ASC
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $highest_bid = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($highest_bid) {
        $winner_id = $highest_bid['user_id'];
        $winning_bid_id = $highest_bid['bid_id'];
        $highest_bid_amount = $highest_bid['bid_amount'];
        $result_status = "sold";
    } else {
        $winner_id = null;
        $winning_bid_id = null;
        $highest_bid_amount = null;
        $result_status = "unsold";
    }

    $sql = "INSERT INTO auction_results
            (product_id, winner_id, winning_bid_id,
             highest_bid, result_status)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "iiids",
        $product_id,
        $winner_id,
        $winning_bid_id,
        $highest_bid_amount,
        $result_status
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Could not save auction result.");
    }

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    header("Location: manage_products.php?ended=success");
    exit();

} catch (Exception $e) {

    mysqli_rollback($conn);

    die("Error: " . htmlspecialchars($e->getMessage()));
}
?>