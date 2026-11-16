<?php
session_start();
include 'includes/db.php';

$error = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, email, and password are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already registered
        $chk = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($chk, "s", $email);
        mysqli_stmt_execute($chk);
        $res = mysqli_stmt_get_result($chk);

        if (mysqli_num_rows($res) > 0) {
            $error = "This email is already registered.";
            mysqli_stmt_close($chk);
        } else {
            mysqli_stmt_close($chk);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $new_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['success'] = "Registration successful!";
                header("Location: index.php");
                exit();
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">
        <h2>Create Account</h2>

        <?php if (!empty($error)) { ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form action="register.php" method="POST">
            <label>Name</label>
            <input type="text" name="name" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Phone</label>
            <input type="text" name="phone">

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" name="register">Register</button>
        </form>

        <p class="text-center">Already have an account? <a href="login.php">Login</a></p>
    </div>

</body>
</html>
