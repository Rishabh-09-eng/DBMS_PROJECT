<?php
    session_start();
    include 'includes/db.php';

    if (isset($_POST['register'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password != $confirm_password) {
        $error = "Passwords do not match!";
    }
    else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, phone, password)
                VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $name,
            $email,
            $phone,
            $hashed_password
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Registration successful! Welcome aboard.";
            header("Location: index.php");
            exit();
        }
        else {
            $error = "Registration failed. This email might already exist.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Online Auction</title>

    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="container">
        <h2>Create Account</h2>

        <?php if (!empty($error)) { ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>

        <form action="register.php" method="POST">

            <label>Full Name</label>
            <input type="text" name="name" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Phone Number</label>
            <input type="text" name="phone">

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" name="register">Register</button>

            <p>Already have an account? <a href="login.php">Login</a></p>

        </form>
    </div>
</body>
</html>