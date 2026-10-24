<?php

require '../session_check.php';
require '../config/database.php';

$id = $_GET['id'];

mysqli_query(
    $conn,
    "DELETE FROM users WHERE id='$id'"
);

header("Location: users.php");
exit;