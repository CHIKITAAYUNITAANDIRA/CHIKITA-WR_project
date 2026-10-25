
<?php
require '../session_check.php';
require '../config/database.php';

// Halaman ini khusus untuk karyawan
if (($_SESSION['role'] ?? '') !== 'karyawan') {
    header('Location: ../login.php');
    exit;
}

// Ambil seluruh data issue
$sql = "SELECT * FROM issue_targets ORDER BY id DESC";
$data = mysqli_query($conn, $sql);

if (!$data) {
    die("Gagal mengambil data issue: " . htmlspecialchars(mysqli_error($conn)));
}

// Fungsi keamanan untuk menampilkan teks
function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$nama = $_SESSION['nama'] ?? 'Karyawan';

// Hitung jumlah status issue
$totalIssue = mysqli_num_rows($data);
$totalOpen = 0;
$totalProgress = 0;
$totalDone = 0;

while ($item = mysqli_fetch_assoc($data)) {
    $statusItem = $item['status'] ?? 'Open';

    if ($statusItem === 'Done') {
        $totalDone++;
    } elseif ($statusItem === 'Progress') {
        $totalProgress++;
    } else {
        $totalOpen++;
    }
}

// Kembalikan posisi hasil query ke awal
mysqli_data_seek($data, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Target - Karyawan</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #eef7ff;
            color: #263445;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(120deg, #c92f49, #ef5368);
            color: white;
            padding: 25px 30px;
            box-shadow: 0 4px 15px rgba(120, 35, 55, 0.14);
        }

        .header-inner,
        .container {
            max-width: 1300px;
            margin-left: auto;
            margin-right: auto;
        }

        .header h1 {
            font-size: 26px;
            margin-bottom: 8px;
        }

        .header p {
            font-size: 13px;
            line-height: 1.6;
        }

        .container {
            margin-top: 27px;
            padding: 0 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-back,
        .btn-wr {
            background: #d9efff;
            color: #245783;
        }

        .btn-back {
            margin-bottom: 22px;
        }

        .btn-back:hover,
        .btn-wr:hover {
            background: #c4e6fb;
        }

        .page-title {
            margin-bottom: 20px;
        }

        .page-title h2 {
            color: #263f63;
            font-size: 23px;
            margin-bottom: 7px;
        }

        .page-title p {
            color: #728096;
            font-size: 13px;
            line-height: 1.6;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: white;
            border: 1px solid #e0edf7;
            border-top: 4px solid #d9364f;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 4px 15px rgba(47, 83, 120, 0.05);
        }

        .summary-card.blue {
            border-top-color: #75bfe9;
        }

        .summary-card.amber {
            border-top-color: #efbd60;
        }

        .summary-card.done {
            border-top-color: #3c9bc8;
        }

        .summary-label {
            color: #728096;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .summary-value {
            color: #d9364f;
            font-size: 26px;
            font-weight: bold;
        }

        .summary-card.blue .summary-value,
        .summary-card.done .summary-value {
            color: #287eac;
        }

        .summary-card.amber .summary-value {
            color: #a87319;
        }

        .notice {
            background: #fff0f1;
            color: #a82337;
            border: 1px solid #ffd1d8;
            border-radius: 9px;
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.7;
        }

        .table-card {
            background: white;
            border: 1px solid #e2edf6;
            border-radius: 13px;
            padding: 21px;
            box-shadow: 0 5px 20px rgba(47, 83, 120, 0.07);
        }

        .table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 17px;
        }

        .table-heading h3 {
            color: #263f63;
            font-size: 17px;
        }

        .table-heading span {
            color: #728096;
            font-size: 12px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        thead {
            background: #dff1ff;
        }

        th {
            text-align: left;
            color: #244b70;
            font-size: 11px;
            padding: 14px 11px;
            border-bottom: 2px solid #c7e5fa;
            white-space: nowrap;
        }

        td {
            padding: 14px 11px;
            border-bottom: 1px solid #edf2f7;
            font-size: 12px;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #f5faff;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
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

        .btn-done {
            background: #e6f5ff;
            color: #21618c;
        }

        .empty {
            text-align: center;
            color: #7b8798;
            padding: 30px;
            line-height: 1.7;
        }

        .footer {
            text-align: center;
            padding: 25px;
            color: #8090a3;
            font-size: 11px;
        }

        @media (max-width: 760px) {
            .header {
                padding: 22px 18px;
            }

            .header h1 {
                font-size: 23px;
            }

            .container {
                padding: 0 12px;
                margin-top: 20px;
            }

            .summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .table-card {
                padding: 14px;
            }

            .table-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 380px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <div class="header-inner">
        <h1>Issue Target</h1>
        <p>
            Halo, <?= e($nama) ?>.
            Lihat target issue dan buka Working Record yang ditugaskan.
        </p>
    </div>
</header>

<main class="container">

    <a href="dashboard.php" class="btn btn-back">
        &larr; Kembali ke Dashboard
    </a>

    <div class="page-title">
        <h2>Daftar Issue Target</h2>
        <p>
            Karyawan dapat melihat issue dan mengelola aktivitas
            pada Working Record sesuai ketentuan.
        </p>
    </div>

    <div class="summary-grid">

        <div class="summary-card">
            <div class="summary-label">Total Issue</div>
            <div class="summary-value">
                <?= number_format($totalIssue) ?>
            </div>
        </div>

        <div class="summary-card blue">
            <div class="summary-label">Open</div>
            <div class="summary-value">
                <?= number_format($totalOpen) ?>
            </div>
        </div>

        <div class="summary-card amber">
            <div class="summary-label">Progress</div>
            <div class="summary-value">
                <?= number_format($totalProgress) ?>
            </div>
        </div>

        <div class="summary-card done">
            <div class="summary-label">Done / OK</div>
            <div class="summary-value">
                <?= number_format($totalDone) ?>
            </div>
        </div>

    </div>

   
    <section class="table-card">

        <div class="table-heading">
            <h3>Data Target Produksi</h3>
            <span><?= number_format($totalIssue) ?> data issue</span>
        </div>

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
                        <th>Working Record</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($totalIssue > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($data)): ?>

                        <?php
                        $status = $row['status'] ?? 'Open';

                        if ($status === 'Done') {
                            $statusClass = 'status-done';
                            $buttonText = 'Lihat / Edit WR';
                            $buttonClass = 'btn-done';
                        } elseif ($status === 'Progress') {
                            $statusClass = 'status-progress';
                            $buttonText = 'Buka WR';
                            $buttonClass = 'btn-wr';
                        } else {
                            $statusClass = 'status-open';
                            $buttonText = 'Buka WR';
                            $buttonClass = 'btn-wr';
                        }
                        ?>

                        <tr>
                            <td><?= (int) $row['id'] ?></td>

                            <td>
                                <?= e($row['tanggal_issue']) ?>
                            </td>

                            <td><?= e($row['model']) ?></td>

                            <td>
                                <?= e($row['lot_no'] ?? '-') ?>
                            </td>

                            <td><?= e($row['line']) ?></td>

                            <td>
                                <?= number_format((int) $row['qty_target']) ?>
                            </td>

                            <td>
                                <span class="status <?= $statusClass ?>">
                                    <?= e($status) ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    href="working_record.php?issue_id=<?= (int) $row['id'] ?>"
                                    class="btn <?= $buttonClass ?>"
                                >
                                    <?= e($buttonText) ?>
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty">
                            Belum ada data issue target.<br>
                            Silakan hubungi admin jika ada issue
                            yang perlu ditambahkan.
                        </td>
                    </tr>

                <?php endif; ?>

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
