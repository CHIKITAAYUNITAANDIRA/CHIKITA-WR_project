
<?php
require '../session_check.php';
require '../config/database.php';

// Pastikan hanya karyawan yang dapat membuka halaman ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    header("Location: ../login.php");
    exit;
}

// Informasi user yang sedang login
$nama = $_SESSION['nama'] ?? $_SESSION['nik'] ?? 'Karyawan';

// Hitung total issue
$queryTotal = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM issue_targets"
);
$totalIssue = mysqli_fetch_assoc($queryTotal)['total'];

// Hitung issue Open
$queryOpen = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM issue_targets
     WHERE status = 'Open'"
);
$totalOpen = mysqli_fetch_assoc($queryOpen)['total'];

// Hitung issue Progress
$queryProgress = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM issue_targets
     WHERE status = 'Progress'"
);
$totalProgress = mysqli_fetch_assoc($queryProgress)['total'];

// Hitung issue Done
$queryDone = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM issue_targets
     WHERE status = 'Done'"
);
$totalDone = mysqli_fetch_assoc($queryDone)['total'];

// Ambil daftar issue dan WR yang sudah ada
$queryIssue = mysqli_query(
    $conn,
    "SELECT
        i.id,
        i.tanggal_issue,
        i.model,
        i.lot_no,
        i.line,
        i.qty_target,
        i.status,
        w.id AS wr_id
     FROM issue_targets i
     LEFT JOIN working_records w
        ON w.issue_id = i.id
     ORDER BY i.tanggal_issue DESC, i.id DESC"
);

if (!$queryIssue) {
    die("Gagal mengambil data issue: " . htmlspecialchars(mysqli_error($conn)));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard Karyawan - Working Record</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f9;
            color: #253047;
        }

        header {
            background: #163b65;
            color: white;
            padding: 20px 30px;
        }

        header h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        header p {
            margin: 0;
        }

        nav {
            background: white;
            padding: 14px 30px;
            border-bottom: 1px solid #ddd;
        }

        nav a {
            display: inline-block;
            margin-right: 20px;
            color: #163b65;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            padding: 25px 30px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(130px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .card p {
            margin: 0 0 10px;
            color: #64748b;
        }

        .card h2 {
            margin: 0;
            font-size: 30px;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .panel h2 {
            margin-top: 0;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 12px 10px;
            border: 1px solid #e1e5eb;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #edf2f7;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .open {
            background: #fff0c2;
            color: #8a5a00;
        }

        .progress {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .done {
            background: #dcfce7;
            color: #166534;
        }

        .btn {
            display: inline-block;
            background: #1769aa;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            white-space: nowrap;
            font-size: 13px;
        }

        .btn:hover {
            background: #104d80;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 25px;
        }

        footer {
            padding: 20px 30px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 750px) {
            header, nav, main, footer {
                padding-left: 15px;
                padding-right: 15px;
            }

            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Working Record Operator PT EX</h1>
    <p>Dashboard Karyawan</p>
</header>

<nav>
    <a href="dashboard.php">Dashboard</a>
    <a href="issue.php">Issue Target</a>
    <a href="../logout.php"
       onclick="return confirm('Yakin ingin logout?')">Logout</a>
</nav>

<main>

    <h2>Selamat datang, <?= htmlspecialchars($nama) ?>!</h2>
    <p>Berikut ringkasan dan daftar Issue Target.</p>

    <section class="cards">

        <div class="card">
            <p>Total Issue</p>
            <h2><?= (int)$totalIssue ?></h2>
        </div>

        <div class="card">
            <p>Open</p>
            <h2><?= (int)$totalOpen ?></h2>
        </div>

        <div class="card">
            <p>Progress</p>
            <h2><?= (int)$totalProgress ?></h2>
        </div>

        <div class="card">
            <p>Done</p>
            <h2><?= (int)$totalDone ?></h2>
        </div>

    </section>

    <section class="panel">

        <h2>Daftar Issue Target</h2>

        <div class="table-wrap">
            <table>

                <thead>
                    <tr>
                        <th>No.</th>
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

                <?php if (mysqli_num_rows($queryIssue) > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = mysqli_fetch_assoc($queryIssue)): ?>

                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <?= htmlspecialchars($row['tanggal_issue']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['model']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['lot_no'] ?? '-') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['line']) ?>
                            </td>

                            <td>
                                <?= (int)$row['qty_target'] ?>
                            </td>

                            <td>
                                <?php
                                $statusClass = 'open';

                                if ($row['status'] === 'Progress') {
                                    $statusClass = 'progress';
                                } elseif ($row['status'] === 'Done') {
                                    $statusClass = 'done';
                                }
                                ?>

                                <span class="status <?= $statusClass ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>

                            <td>
                                <a class="btn"
                                   href="working_record.php?issue_id=<?= (int)$row['id'] ?>">
                                    <?= $row['wr_id'] ? 'Lihat WR' : 'Buat WR' ?>
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty">
                            Belum ada Issue Target.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>

    </section>

</main>

<footer>
    Working Record Operator PT EX &copy; <?= date('Y') ?>
</footer>

</body>
</html>
