```php
<?php

require '../session_check.php';

if (($_SESSION['role'] ?? '') != 'admin') {
    header("Location: ../login.php");
    exit;
}

$nama = htmlspecialchars(
    $_SESSION['nama'] ?? 'Administrator',
    ENT_QUOTES,
    'UTF-8'
);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard Admin - Working Record PT EX</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eef6ff;
            color: #263449;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            padding: 28px 18px;
            background: linear-gradient(180deg, #dceeff, #c5e2ff);
            border-right: 1px solid #b8d8f5;
        }

        .brand {
            padding: 10px 8px 28px;
            border-bottom: 2px solid #ffffff;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e53945;
            color: white;
            border-radius: 14px;
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 14px;
        }

        .brand h2 {
            font-size: 19px;
            color: #173c68;
            line-height: 1.4;
        }

        .brand p {
            font-size: 12px;
            color: #56718f;
            margin-top: 5px;
        }

        .menu-title {
            margin: 28px 10px 12px;
            color: #6b829c;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 13px;
            margin-bottom: 8px;
            color: #294a70;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: 0.2s;
        }

        .menu a:hover,
        .menu a.active {
            background: #ffffff;
            color: #d92f3c;
            box-shadow: 0 4px 12px rgba(43, 91, 140, 0.08);
        }

        .menu-icon {
            width: 24px;
            text-align: center;
            font-size: 18px;
        }

        .sidebar-bottom {
            position: absolute;
            bottom: 25px;
            left: 18px;
            right: 18px;
        }

        .logout {
            display: block;
            text-align: center;
            padding: 13px;
            color: #d92f3c;
            background: #fff0f1;
            border: 1px solid #ffd1d5;
            border-radius: 10px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: 0.2s;
        }

        .logout:hover {
            background: #e53945;
            color: white;
        }

        /* Konten utama */
        .main {
            margin-left: 250px;
            padding: 30px 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .topbar h1 {
            font-size: 26px;
            color: #173c68;
        }

        .topbar p {
            margin-top: 7px;
            color: #7a8ba1;
            font-size: 14px;
        }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: white;
            border: 1px solid #e0eaf5;
            border-radius: 12px;
        }

        .avatar {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #ffe1e4;
            color: #d92f3c;
            font-weight: bold;
        }

        .admin-name {
            font-size: 13px;
            font-weight: bold;
            color: #29415e;
        }

        .admin-role {
            margin-top: 4px;
            font-size: 11px;
            color: #8493a6;
        }

        /* Banner sambutan */
        .welcome {
            position: relative;
            overflow: hidden;
            padding: 30px;
            margin-bottom: 28px;
            border-radius: 18px;
            color: white;
            background: linear-gradient(115deg, #e53945, #f05b68);
            box-shadow: 0 8px 22px rgba(229, 57, 69, 0.16);
        }

        .welcome::after {
            content: "";
            position: absolute;
            width: 190px;
            height: 190px;
            border: 35px solid rgba(255, 255, 255, 0.10);
            border-radius: 50%;
            right: -35px;
            top: -75px;
        }

        .welcome h2 {
            position: relative;
            z-index: 1;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .welcome p {
            position: relative;
            z-index: 1;
            font-size: 14px;
            line-height: 1.7;
            color: #fff0f1;
            max-width: 550px;
        }

        .welcome-label {
            display: inline-block;
            position: relative;
            z-index: 1;
            margin-bottom: 13px;
            padding: 6px 10px;
            background: rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        /* Kartu menu */
        .section-heading {
            margin-bottom: 16px;
        }

        .section-heading h2 {
            font-size: 19px;
            color: #173c68;
        }

        .section-heading p {
            margin-top: 6px;
            color: #8290a2;
            font-size: 13px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .card {
            display: flex;
            flex-direction: column;
            min-height: 225px;
            padding: 23px;
            background: #ffffff;
            border: 1px solid #e0eaf5;
            border-radius: 15px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 15px rgba(36, 73, 115, 0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(36, 73, 115, 0.10);
        }

        .card-icon {
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .icon-red {
            color: #d92f3c;
            background: #fff0f1;
        }

        .icon-blue {
            color: #2675bb;
            background: #e5f3ff;
        }

        .card h3 {
            margin-bottom: 9px;
            font-size: 17px;
            color: #243d5b;
        }

        .card p {
            color: #8492a5;
            font-size: 13px;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .card-action {
            margin-top: auto;
            color: #d92f3c;
            font-size: 13px;
            font-weight: bold;
        }

        .card:nth-child(2) .card-action {
            color: #2675bb;
        }

        .footer {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #dce7f3;
            color: #94a0af;
            font-size: 12px;
            text-align: center;
        }

        /* Tampilan tablet dan HP */
        @media (max-width: 1050px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 18px;
            }

            .brand {
                padding-bottom: 15px;
            }

            .menu-title {
                margin-top: 18px;
            }

            .sidebar-bottom {
                position: static;
                margin-top: 18px;
            }

            .main {
                margin-left: 0;
                padding: 22px 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .topbar h1 {
                font-size: 23px;
            }

            .cards {
                grid-template-columns: 1fr;
            }

            .welcome {
                padding: 24px;
            }

            .welcome h2 {
                font-size: 21px;
            }
        }
    </style>
</head>

<body>

<aside class="sidebar">

    <div class="brand">
        <div class="brand-icon">WR</div>
        <h2>WORKING RECORD</h2>
        <p>Operator Management System</p>
    </div>

    <div class="menu-title">Menu Utama</div>

    <nav class="menu">
        <a href="dashboard.php" class="active">
            <span class="menu-icon">&#8962;</span>
            Dashboard
        </a>

        <a href="users.php">
            <span class="menu-icon">&#9787;</span>
            Data User
        </a>

        <a href="issue.php">
            <span class="menu-icon">&#9776;</span>
            Issue Target
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a
            class="logout"
            href="../logout.php"
            onclick="return confirm('Yakin ingin logout?')"
        >
            &#10140; Logout
        </a>
    </div>

</aside>

<main class="main">

    <div class="topbar">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Kelola pencatatan Working Record Operator PT EX.</p>
        </div>

        <div class="admin-badge">
            <div class="avatar">A</div>
            <div>
                <div class="admin-name"><?= $nama ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
    </div>

    <section class="welcome">
        <span class="welcome-label">ADMINISTRATOR PANEL</span>

        <h2>Selamat datang, <?= $nama ?>!</h2>

        <p>
            Kelola data pengguna dan pantau Issue Target melalui
            dashboard administrasi ini. Working Record dapat dibuka
            langsung dari tombol Buat / Lihat WR pada halaman Issue Target.
        </p>
    </section>

    <section>
        <div class="section-heading">
            <h2>Menu Pengelolaan</h2>
            <p>Pilih menu yang ingin kamu buka.</p>
        </div>

        <div class="cards">

            <a class="card" href="users.php">
                <div class="card-icon icon-red">&#9787;</div>

                <h3>Data User</h3>

                <p>
                    Lihat daftar pengguna serta kelola data akun
                    admin dan karyawan.
                </p>

                <span class="card-action">
                    Buka Data User &rarr;
                </span>
            </a>

            <a class="card" href="issue.php">
                <div class="card-icon icon-blue">&#9776;</div>

                <h3>Issue Target</h3>

                <p>
                    Kelola tanggal issue, model, line, lot number,
                    qty target, dan status pengerjaan. Buka Working
                    Record melalui tombol pada daftar issue.
                </p>

                <span class="card-action">
                    Buka Issue Target &rarr;
                </span>
            </a>

        </div>
    </section>

    <div class="footer">
        &copy; <?= date('Y') ?> Working Record Operator PT EX
        &mdash; Admin Dashboard
    </div>

</main>

</body>
</html>
```