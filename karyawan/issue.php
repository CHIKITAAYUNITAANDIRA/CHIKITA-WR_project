<?php

require '../session_check.php';
require '../config/database.php';

// Mengambil data issue, urutan terbaru berada di atas
$data = mysqli_query(
    $conn,
    "SELECT * FROM issue_targets ORDER BY id DESC"
);

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
    <title>Issue Target</title>

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
            padding: 25px 35px;
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
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-back {
            background: #d9efff;
            color: #245783;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: #c3e5ff;
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

        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 22px;
        }

        .page-title h2 {
            font-size: 24px;
            color: #263f63;
            margin-bottom: 6px;
        }

        .page-title p {
            font-size: 14px;
            color: #728096;
        }

        .table-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 5px 22px rgba(47, 83, 120, 0.08);
        }

        .table-heading {
            color: #263f63;
            font-size: 18px;
            margin-bottom: 18px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        thead {
            background: #dff1ff;
        }

        th {
            color: #244b70;
            font-size: 13px;
            text-align: left;
            padding: 15px 13px;
            border-bottom: 2px solid #c7e5fa;
            white-space: nowrap;
        }

        td {
            padding: 15px 13px;
            border-bottom: 1px solid #edf2f7;
            font-size: 13px;
        }

        tbody tr:hover {
            background: #f4faff;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-open {
            background: #ffe1e6;
            color: #bd2941;
        }

        .status-progress {
            background: #fff0d8;
            color: #96600b;
        }

        .status-done {
            background: #dff3ff;
            color: #21618c;
        }

        .btn-wr {
            background: #d9efff;
            color: #245783;
            white-space: nowrap;
        }

        .btn-wr:hover {
            background: #c3e5ff;
        }

        .empty {
            text-align: center;
            color: #7b8798;
            padding: 30px;
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
        <h1>Issue Target</h1>
        <p>Selamat datang, <?= $nama; ?>. Kelola target issue dan Working Record.</p>
    </header>

    <main class="container">

        <a href="dashboard.php" class="btn btn-back">
            &larr; Kembali ke Dashboard
        </a>

        <div class="top-section">
            <div class="page-title">
                <h2>Daftar Issue Target</h2>
                <p>Informasi target berdasarkan tanggal, model, line, dan jumlah.</p>
            </div>

            <a href="issue_add.php" class="btn btn-add">
                + Tambah Issue
            </a>
        </div>

        <section class="table-card">
            <h3 class="table-heading">Data Target Produksi</h3>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal Issue</th>
                            <th>Model</th>
                            <th>Lot No</th>
                            <th>Line</th>
                            <th>Qty Target</th>
                            <th>Status</th>
                            <th>WR</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($data && mysqli_num_rows($data) > 0) { ?>

                            <?php while ($row = mysqli_fetch_assoc($data)) { ?>

                                <tr>
                                    <td>
                                        <?= htmlspecialchars((string) $row['id']); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string) $row['tanggal_issue']); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string) $row['model']); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string) ($row['lot_no'] ?? '-')); ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars((string) $row['line']); ?>
                                    </td>

                                    <td>
                                        <?= number_format((int) $row['qty_target']); ?>
                                    </td>

                                    <td>
                                        <?php
                                        $status = $row['status'] ?? 'Open';

                                        if ($status == 'Done') {
                                            $statusClass = 'status-done';
                                        } elseif ($status == 'Progress') {
                                            $statusClass = 'status-progress';
                                        } else {
                                            $statusClass = 'status-open';
                                        }
                                        ?>

                                        <span class="status <?= $statusClass; ?>">
                                            <?= htmlspecialchars((string) $status); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            href="working_record.php?issue_id=<?= urlencode((string) $row['id']); ?>"
                                            class="btn btn-wr"
                                        >
                                            Buat / Lihat WR
                                        </a>
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="8" class="empty">
                                    Belum ada data issue target.
                                    Silakan klik tombol Tambah Issue.
                                </td>
                            </tr>

                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <footer class="footer">
        Sistem Pencatatan Working Record Operator PT EX
    </footer>

</body>
</html>
