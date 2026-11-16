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

// Handle Add Address
if (isset($_POST['add_address'])) {
    $address_line = trim($_POST['address_line']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $country = trim($_POST['country']) ?: 'USA';

    if (empty($address_line) || empty($city) || empty($state) || empty($pincode)) {
        $error = "All address fields are required.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO addresses (user_id, address_line, city, state, pincode, country, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $address_line, $city, $state, $pincode, $country);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Address added successfully!";
        } else {
            $error = "Failed to add address: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Delete Address
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    // Check if address is being used by an active delivery
    $chk = mysqli_query($conn, "SELECT delivery_id FROM deliveries WHERE address_id = $delete_id");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $error = "Cannot delete this address because it is associated with an active delivery.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM addresses WHERE address_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $delete_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Address removed.";
        } else {
            $error = "Failed to delete address.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch user's addresses
$stmt = mysqli_prepare($conn, "SELECT * FROM addresses WHERE user_id = ? ORDER BY address_id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Delivery Addresses - BidZone</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <div class="table-container">

        <div class="page-header">
            <h3>My Shipping Addresses</h3>
        </div>

        <?php if (!empty($success)) { ?>
            <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php } ?>

        <?php if (!empty($error)) { ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <!-- Saved Addresses -->
        <h4>Saved Addresses</h4>
        <?php if ($res && mysqli_num_rows($res) > 0) { ?>
            <table>
                <tr>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Postal Code</th>
                    <th>Country</th>
                    <th>Action</th>
                </tr>
                <?php while ($addr = mysqli_fetch_assoc($res)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($addr['address_line']); ?></td>
                        <td><?php echo htmlspecialchars($addr['city']); ?></td>
                        <td><?php echo htmlspecialchars($addr['state']); ?></td>
                        <td><?php echo htmlspecialchars($addr['pincode']); ?></td>
                        <td><?php echo htmlspecialchars($addr['country']); ?></td>
                        <td>
                            <a href="addresses.php?delete=<?php echo $addr['address_id']; ?>" onclick="return confirm('Delete this address?');" class="text-danger">Remove</a>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="muted">No shipping addresses saved yet. Add one below.</p>
        <?php } ?>

        <!-- Add Address Form -->
        <div class="card">
            <h4>Add New Shipping Address</h4>
            <form action="addresses.php" method="POST">
                <label>Street Address</label>
                <input type="text" name="address_line" required placeholder="123 Auction Way, Apt 4B">

                <div class="form-row">
                    <div>
                        <label>City</label>
                        <input type="text" name="city" required placeholder="New York">
                    </div>
                    <div>
                        <label>State / Province</label>
                        <input type="text" name="state" required placeholder="NY">
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>Postal Code</label>
                        <input type="text" name="pincode" required placeholder="10001">
                    </div>
                    <div>
                        <label>Country</label>
                        <input type="text" name="country" value="United States" required>
                    </div>
                </div>

                <button type="submit" name="add_address">Save Address</button>
            </form>
        </div>

    </div>

</body>
</html>
