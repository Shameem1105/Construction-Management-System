<?php
session_start();
include "db.php";

$email    = $_POST['email'];
$password = md5($_POST['password']);   // hash to match stored MD5

$sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    $_SESSION['user'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    header("Location: dashboard.php");
} else {
    echo "Invalid Login";
}
?>