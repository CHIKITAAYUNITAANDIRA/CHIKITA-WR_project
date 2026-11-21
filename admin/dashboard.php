
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
    min-height: 100vh;
    color: #263449;

    /* Wallpaper gambar */
    background:
        linear-gradient(
            120deg,
            rgba(224, 240, 255, 0.88),
            rgba(255, 238, 240, 0.82)
        ),
        url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&w=1920&q=85');

    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}

/* SIDEBAR */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    padding: 28px 18px;

    background: rgba(220, 238, 255, 0.92);
    backdrop-filter: blur(15px);

    border-right: 1px solid rgba(255,255,255,0.8);
    box-shadow: 5px 0 25px rgba(30,70,110,0.08);
    z-index: 10;
}

.brand {
    padding: 10px 8px 28px;
    border-bottom: 2px solid white;
}

.brand-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;

    background: linear-gradient(135deg, #e53945, #ff7882);
    color: white;
    border-radius: 15px;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 14px;
    box-shadow: 0 5px 15px rgba(229,57,69,0.25);
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
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    transition: 0.25s;
}

.menu a:hover,
.menu a.active {
    background: white;
    color: #d92f3c;
    transform: translateX(4px);
    box-shadow: 0 5px 15px rgba(43,91,140,0.10);
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
    border-radius: 11px;

    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    transition: 0.25s;
}

.logout:hover {
    background: #e53945;
    color: white;
}

/* KONTEN */
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
    font-size: 27px;
    color: #173c68;
}

.topbar p {
    margin-top: 7px;
    color: #60758e;
    font-size: 14px;
}

.admin-badge {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;

    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(10px);
    border: 1px solid white;
    border-radius: 13px;
    box-shadow: 0 5px 18px rgba(30,70,110,0.07);
}

.avatar {
    width: 40px;
    height: 40px;
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

/* BANNER */
.welcome {
    position: relative;
    overflow: hidden;
    padding: 35px;
    margin-bottom: 30px;
    border-radius: 20px;
    color: white;

    background:
        linear-gradient(
            110deg,
            rgba(194, 35, 52, 0.94),
            rgba(229, 57, 69, 0.82)
        ),
        url('https://images.unsplash.com/photo-1567789884554-0b844b597180?auto=format&fit=crop&w=1200&q=80');

    background-size: cover;
    background-position: center;

    box-shadow: 0 10px 28px rgba(229,57,69,0.20);
}

.welcome::after {
    content: "";
    position: absolute;
    width: 210px;
    height: 210px;
    border: 35px solid rgba(255,255,255,0.12);
    border-radius: 50%;
    right: -35px;
    top: -80px;
}

.welcome h2 {
    position: relative;
    z-index: 1;
    font-size: 25px;
    margin-bottom: 12px;
}

.welcome p {
    position: relative;
    z-index: 1;
    font-size: 14px;
    line-height: 1.8;
    color: #fff0f1;
    max-width: 570px;
}

.welcome-label {
    display: inline-block;
    position: relative;
    z-index: 1;
    margin-bottom: 15px;
    padding: 7px 12px;

    background: rgba(255,255,255,0.20);
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 1px;
}

/* JUDUL MENU */
.section-heading {
    margin-bottom: 17px;
}

.section-heading h2 {
    font-size: 20px;
    color: #173c68;
}

.section-heading p {
    margin-top: 6px;
    color: #60758e;
    font-size: 13px;
}

/* KARTU */
.cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.card {
    display: flex;
    flex-direction: column;
    min-height: 230px;
    padding: 25px;

    background: rgba(255,255,255,0.90);
    backdrop-filter: blur(12px);

    border: 1px solid rgba(255,255,255,0.95);
    border-radius: 18px;
    text-decoration: none;
    color: inherit;

    box-shadow: 0 7px 22px rgba(36,73,115,0.08);
    transition: 0.25s;
}

.card:hover {
    transform: translateY(-6px);
    background: white;
    box-shadow: 0 13px 28px rgba(36,73,115,0.15);
}

.card-icon {
    width: 55px;
    height: 55px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 15px;
    font-size: 25px;
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
    margin-bottom: 10px;
    font-size: 18px;
    color: #243d5b;
}

.card p {
    color: #71839a;
    font-size: 13px;
    line-height: 1.8;
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

/* FOOTER */
.footer {
    margin-top: 38px;
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.8);
    color: #63758b;
    font-size: 12px;
    text-align: center;
}

/* RESPONSIVE */
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
        padding: 25px;
    }

    .welcome h2 {
        font-size: 21px;
    }

    body {
        background-attachment: scroll;
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
        &copy; <?= date('Y') ?> Copyright © 2026 Debby Fransurya, S.Kom.
    </div>

</main>

</body>
</html>
