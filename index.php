<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.html");
} else {
    header("Location: dashboard.php");
}
?>