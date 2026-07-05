<?php
include "db.php";

$id = $_POST['id'];
$shift = $_POST['shift'];

if ($shift === "am") {
    $conn->query("UPDATE workers SET shift_am = NOT shift_am WHERE id=$id");
} else {
    $conn->query("UPDATE workers SET shift_pm = NOT shift_pm WHERE id=$id");
}

echo "success";
?>