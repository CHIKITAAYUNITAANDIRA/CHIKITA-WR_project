<?php
require '../session_check.php';
require '../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    exit('Koneksi database tidak ditemukan. Periksa file config/database.php.');
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$status_filter = $_GET['status'] ?? 'Semua';
$allowed_status = ['Semua', 'Open', 'Progress', 'Done'];
if (!in_array($status_filter, $allowed_status, true)) {
    $status_filter = 'Semua';
}

if ($status_filter === 'Semua') {
    $sql = "SELECT i.*,
                   (SELECT COUNT(*) FROM working_records wr WHERE wr.issue_id = i.id) AS jumlah_wr
            FROM issue_targets i
            ORDER BY i.tanggal_issue DESC, i.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
} else {
    $sql = "SELECT i.*,
                   (SELECT COUNT(*) FROM working_records wr WHERE wr.issue_id = i.id) AS jumlah_wr
            FROM issue_targets i
            WHERE i.status = ?
            ORDER BY i.tanggal_issue DESC, i.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $status_filter);
}

if (!$stmt) {
    exit('Query daftar issue gagal disiapkan. Periksa struktur database.');
}

mysqli_stmt_execute($stmt);
$issues = mysqli_stmt_get_result($stmt);

// Ringkasan total issue berdasarkan status.
$summary_sql = "SELECT COUNT(*) AS total,
                       SUM(status = 'Open') AS total_open,
                       SUM(status = 'Progress') AS total_progress,
                       SUM(status = 'Done') AS total_done
                FROM issue_targets";
$summary_result = mysqli_query($conn, $summary_sql);
$summary = $summary_result ? mysqli_fetch_assoc($summary_result) : [
    'total' => 0, 'total_open' => 0, 'total_progress' => 0, 'total_done' => 0
];

$nama_login = $_SESSION['nama'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Issue Target | Working Record</title>
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        color: #263445;
        background: #eef6fc;
    }
    .topbar {
        background: linear-gradient(120deg, #c92743, #e94d63);
        color: #fff;
        padding: 24px clamp(18px, 4vw, 42px);
        box-shadow: 0 5px 18px rgba(150, 32, 55, .16);
    }
    .topbar-inner {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }
    .topbar h1 { margin: 0 0 7px; font-size: clamp(22px, 3vw, 29px); }
    .topbar p { margin: 0; font-size: 13px; line-height: 1.6; opacity: .95; }
    .container { max-width: 1400px; margin: 26px auto; padding: 0 18px; }
    .nav-actions { display: flex; gap: 9px; flex-wrap: wrap; margin-bottom: 22px; }
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        border: 0; border-radius: 8px; padding: 10px 14px; text-decoration: none;
        font-size: 12px; font-weight: 700; cursor: pointer; font-family: inherit;
        transition: .18s ease;
    }
    .btn:hover { transform: translateY(-1px); }
    .btn-blue { color: #245783; background: #dff1ff; border: 1px solid #c8e6fa; }
    .btn-red { color: #fff; background: #d9364f; }
    .btn-red:hover { background: #bd2941; }
    .btn-logout { color: #bd2941; background: #fff0f2; border: 1px solid #ffd7de; }
    .page-title { display:flex; justify-content:space-between; align-items:flex-end; gap:14px; flex-wrap:wrap; margin-bottom:18px; }
    .page-title h2 { margin:0 0 6px; color:#263f63; font-size:23px; }
    .page-title p { margin:0; color:#748397; font-size:13px; line-height:1.6; }
    .summary-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:14px; margin-bottom:20px; }
    .summary-card { background:#fff; border:1px solid #e2edf6; border-radius:12px; padding:18px; box-shadow:0 4px 16px rgba(47,83,120,.05); }
    .summary-card .label { color:#718198; font-size:12px; margin-bottom:9px; }
    .summary-card .value { font-size:27px; font-weight:800; color:#245783; }
    .summary-card.red { background:#fff5f6; border-color:#ffdee3; }
    .summary-card.red .value { color:#d9364f; }
    .summary-card.orange .value { color:#a86a12; }
    .card { background:#fff; border:1px solid #e2edf6; border-radius:12px; padding:20px; box-shadow:0 4px 18px rgba(47,83,120,.06); margin-bottom:20px; }
    .filter-row { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
    .filter-row h3 { margin:0; color:#263f63; font-size:17px; }
    .filter-form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    select { border:1px solid #cbdff0; background:#fbfdff; border-radius:8px; padding:10px 12px; color:#263445; font-family:inherit; font-size:12px; }
    .table-wrap { overflow-x:auto; width:100%; }
    table { border-collapse:collapse; width:100%; min-width:980px; }
    thead { background:#dff1ff; }
    th { color:#244b70; font-size:11px; text-align:left; padding:13px 11px; border-bottom:2px solid #c7e5fa; white-space:nowrap; }
    td { font-size:12px; padding:13px 11px; border-bottom:1px solid #edf2f7; vertical-align:middle; }
    tbody tr:hover { background:#f6fbff; }
    tbody tr:last-child td { border-bottom:0; }
    .status { display:inline-block; padding:6px 9px; border-radius:30px; font-size:10px; font-weight:800; white-space:nowrap; }
    .status-open { color:#b92740; background:#ffe4e9; }
    .status-progress { color:#8d5c0a; background:#fff0d5; }
    .status-done { color:#21618c; background:#dff3ff; }
    .action-cell { display:flex; gap:6px; flex-wrap:wrap; }
    .btn-small { padding:8px 10px; font-size:11px; white-space:nowrap; }
    .empty { padding:32px 12px; text-align:center; color:#7a8798; }
    .footer { text-align:center; padding:18px; color:#8391a3; font-size:11px; }
    @media(max-width:850px) { .summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:480px) { .summary-grid { grid-template-columns:1fr 1fr; gap:9px; } .summary-card { padding:13px; } .summary-card .value { font-size:22px; } .card { padding:14px; } }
</style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <div>
            <h1>Data Issue Target</h1>
            <p>Sistem Pencatatan Working Record Operator PT EX</p>
        </div>
        <div style="font-size:13px;">Halo, <strong><?= e($nama_login) ?></strong></div>
    </div>
</header>

<main class="container">
    <nav class="nav-actions">
        <a class="btn btn-blue" href="dashboard.php">← Kembali ke Dashboard</a>
        <a class="btn btn-red" href="issue_add.php">＋ Tambah Issue Target</a>
        <a class="btn btn-logout" href="../logout.php" onclick="return confirm('Yakin ingin logout?')">Logout</a>
    </nav>

    <div class="page-title">
        <div>
            <h2>Daftar Issue Target</h2>
            <p>Pilih issue untuk membuka Working Record atau membuat Working Record baru.</p>
        </div>
    </div>

    <section class="summary-grid">
        <div class="summary-card red"><div class="label">Total Issue</div><div class="value"><?= number_format((int)($summary['total'] ?? 0)) ?></div></div>
        <div class="summary-card"><div class="label">Open</div><div class="value"><?= number_format((int)($summary['total_open'] ?? 0)) ?></div></div>
        <div class="summary-card orange"><div class="label">Progress</div><div class="value"><?= number_format((int)($summary['total_progress'] ?? 0)) ?></div></div>
        <div class="summary-card"><div class="label">Done</div><div class="value"><?= number_format((int)($summary['total_done'] ?? 0)) ?></div></div>
    </section>

    <section class="card">
        <div class="filter-row">
            <h3>Daftar Issue</h3>
            <form class="filter-form" method="GET">
                <label for="status" style="font-size:12px;font-weight:bold;color:#354d69;">Filter status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <?php foreach ($allowed_status as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status_filter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn btn-blue" type="submit">Terapkan</button></noscript>
            </form>
        </div>
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
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($issues && mysqli_num_rows($issues) > 0): ?>
                    <?php $no = 1; while ($issue = mysqli_fetch_assoc($issues)): ?>
                        <?php
                            $status = $issue['status'] ?? 'Open';
                            $status_class = $status === 'Done' ? 'status-done' : ($status === 'Progress' ? 'status-progress' : 'status-open');
                            $wr_exists = (int)($issue['jumlah_wr'] ?? 0) > 0;
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= e($issue['tanggal_issue']) ?></td>
                            <td><strong><?= e($issue['model']) ?></strong></td>
                            <td><?= e(($issue['lot_no'] ?? '') !== '' ? $issue['lot_no'] : '-') ?></td>
                            <td><?= e($issue['line']) ?></td>
                            <td><?= number_format((int)$issue['qty_target']) ?></td>
                            <td><span class="status <?= e($status_class) ?>"><?= e($status) ?></span></td>
                            <td><?= $wr_exists ? '<span style="color:#21618c;font-weight:bold;">Sudah ada</span>' : '<span style="color:#bd2941;font-weight:bold;">Belum dibuat</span>' ?></td>
                            <td>
                                <div class="action-cell">
                                    <a class="btn btn-blue btn-small" href="../karyawan/working_record.php?issue_id=<?= (int)$issue['id'] ?>">
                                        <?= $wr_exists ? 'Buka WR' : 'Buat / Buka WR' ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="empty">Belum ada issue target untuk status ini. Klik “Tambah Issue Target” untuk menambahkan data.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<footer class="footer">Sistem Pencatatan Working Record Operator PT EX</footer>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
?>
