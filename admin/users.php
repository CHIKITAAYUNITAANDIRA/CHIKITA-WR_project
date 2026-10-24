```php
<?php
require '../session_check.php';
require '../config/database.php';

// Mengambil seluruh data user
$data = mysqli_query($conn, "SELECT * FROM users");

$nama = htmlspecialchars($_SESSION['nama'] ?? 'Administrator', ENT_QUOTES, 'UTF-8');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data User</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eef8ff;
            color: #263445;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(120deg, #d9364f, #ef5368);
            color: white;
            padding: 24px 35px;
            box-shadow: 0 4px 14px rgba(120, 35, 55, 0.15);
        }

        .header h1 {
            font-size: 27px;
            margin-bottom: 7px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.95;
        }

        .container {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .page-title h2 {
            color: #263f63;
            font-size: 24px;
            margin-bottom: 6px;
        }

        .page-title p {
            color: #728096;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-add {
            background: #d9364f;
            color: white;
            box-shadow: 0 4px 10px rgba(217, 54, 79, 0.18);
        }

        .btn-add:hover {
            background: #bd2941;
            transform: translateY(-1px);
        }

        .btn-back {
            background: #d9efff;
            color: #245783;
            margin-bottom: 18px;
        }

        .btn-back:hover {
            background: #c3e5ff;
        }

        .table-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 5px 22px rgba(47, 83, 120, 0.08);
            overflow-x: auto;
        }

        .table-heading {
            color: #263f63;
            font-size: 18px;
            margin-bottom: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        thead {
            background: #dff1ff;
        }

        th {
            color: #244b70;
            font-size: 13px;
            text-align: left;
            padding: 15px 14px;
            border-bottom: 2px solid #c7e5fa;
        }

        td {
            padding: 15px 14px;
            border-bottom: 1px solid #edf2f7;
            font-size: 14px;
        }

        tbody tr:hover {
            background: #f4faff;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .role {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: capitalize;
        }

        .role-admin {
            background: #ffe1e6;
            color: #bd2941;
        }

        .role-karyawan {
            background: #dff3ff;
            color: #21618c;
        }

        .btn-delete {
            display: inline-block;
            background: #fff0f1;
            color: #c52e43;
            border: 1px solid #ffd4da;
            padding: 8px 12px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
        }

        .btn-delete:hover {
            background: #d9364f;
            color: white;
            border-color: #d9364f;
        }

        .empty {
            text-align: center;
            color: #7b8798;
            padding: 28px;
        }

        .footer {
            text-align: center;
            padding: 22px;
            color: #8090a3;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .header {
                padding: 22px 20px;
            }

            .header h1 {
                font-size: 23px;
            }

            .container {
                margin-top: 22px;
                padding: 0 12px;
            }

            .table-card {
                padding: 14px;
            }

            .top-section {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <h1>Data User</h1>
        <p>Selamat datang, <?= $nama; ?>. Kelola data pengguna Working Record di sini.</p>
    </header>

    <main class="container">

        <a href="dashboard.php" class="btn btn-back">
            &larr; Kembali ke Dashboard
        </a>

        <div class="top-section">
            <div class="page-title">
                <h2>Daftar Pengguna</h2>
                <p>Data akun administrator dan karyawan.</p>
            </div>

            <a href="user_add.php" class="btn btn-add">
                + Tambah User
            </a>
        </div>

        <section class="table-card">
            <h3 class="table-heading">Informasi Pengguna</h3>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NIK</th>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($data && mysqli_num_rows($data) > 0) { ?>

                        <?php while ($row = mysqli_fetch_assoc($data)) { ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']); ?></td>
                                <td><?= htmlspecialchars($row['nik']); ?></td>
                                <td><?= htmlspecialchars($row['nama']); ?></td>
                                <td>
                                    <?php if ($row['role'] == 'admin') { ?>
                                        <span class="role role-admin">Admin</span>
                                    <?php } else { ?>
                                        <span class="role role-karyawan">Karyawan</span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <a
                                        href="user_delete.php?id=<?= urlencode($row['id']); ?>"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin hapus user ini?')"
                                    >
                                        Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>

                    <?php } else { ?>
                        <tr>
                            <td colspan="5" class="empty">
                                Belum ada data user.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>

    </main>

    <footer class="footer">
        Sistem Pencatatan Working Record Operator PT EX
    </footer>

</body>
</html>
```