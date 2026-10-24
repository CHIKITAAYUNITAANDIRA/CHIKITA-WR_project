<?php
session_start();
require 'config/database.php';

if(isset($_POST['login']))
{
    $nik = mysqli_real_escape_string($conn,$_POST['nik']);
    $password = $_POST['password'];

    $query = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE nik='$nik'"
    );

    if(mysqli_num_rows($query) == 1)
    {
        $user = mysqli_fetch_assoc($query);

        if(password_verify($password,$user['password']))
        {
            $_SESSION['id'] = $user['id'];
            $_SESSION['nik'] = $user['nik'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            if($user['role'] == 'admin')
            {
                header("Location: admin/dashboard.php");
            }
            else
            {
                header("Location: karyawan/dashboard.php");
            }

            exit;
        }
    }

    header("Location: login.php?error=1");
}