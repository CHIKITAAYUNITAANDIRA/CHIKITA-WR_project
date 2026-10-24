<?php

require '../session_check.php';
require '../config/database.php';

if(isset($_POST['simpan']))
{
    $tanggal_issue = $_POST['tanggal_issue'];
    $model = $_POST['model'];
    $lot_no = $_POST['lot_no'];
    $line = $_POST['line'];
    $qty_target = $_POST['qty_target'];

    $created_by = $_SESSION['id'];

    mysqli_query($conn,"
        INSERT INTO issue_targets
        (
            tanggal_issue,
            model,
            lot_no,
            line,
            qty_target,
            status,
            created_by
        )
        VALUES
        (
            '$tanggal_issue',
            '$model',
            '$lot_no',
            '$line',
            '$qty_target',
            'Open',
            '$created_by'
        )
    ");

    header("Location: issue.php");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Issue</title>
</head>
<body>

<h2>Tambah Issue</h2>

<form method="POST">

    Tanggal Issue
    <br>
    <input type="date" name="tanggal_issue" required>

    <br><br>

    Model
    <br>
    <input type="text" name="model" required>

    <br><br>

    Lot No
    <br>
    <input type="text" name="lot_no">

    <br><br>

    Line
    <br>
    <input type="text" name="line" required>

    <br><br>

    Qty Target
    <br>
    <input type="number" name="qty_target" required>

    <br><br>

    <button type="submit" name="simpan">
        Simpan
    </button>

</form>

<br>

<a href="issue.php">
    Kembali
</a>

</body>
</html>