<?php
session_start();
include 'includes/db.php';

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];

            header("Location: index.php");
            exit();

        }
        else {
            echo "<script>alert('Incorrect password!');</script>";
        }

    }
    else {
        echo "<script>alert('User does not exist!');</script>";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Online Auction</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

    <div class="container">

        <h2>Login</h2>

        <form action="login.php" method="POST">

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" name="login">Login</button>

        </form>

        <p>Don't have an account? <a href="register.php">Register</a></p>

    </div>

</body>
</html>