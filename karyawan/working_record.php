
<?php
require '../session_check.php';
require '../config/database.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['admin', 'karyawan'], true)) {
    header('Location: ../login.php');
    exit;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getTotals(mysqli $conn, int $wrId): array
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            COALESCE(SUM(qty_hasil), 0) AS total_qty,
            COALESCE(SUM(total_jam), 0) AS total_jam
         FROM working_record_details
         WHERE wr_id = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $wrId);
    mysqli_stmt_execute($stmt);
    $totals = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $totals ?: ['total_qty' => 0, 'total_jam' => 0];
}

function calculateHours(string $start, string $finish): float
{
    $startParts = array_map('intval', explode(':', $start));
    $finishParts = array_map('intval', explode(':', $finish));

    if (count($startParts) < 2 || count($finishParts) < 2) {
        return 0;
    }

    $startMinutes = $startParts[0] * 60 + $startParts[1];
    $finishMinutes = $finishParts[0] * 60 + $finishParts[1];

    // Jika melewati tengah malam.
    if ($finishMinutes < $startMinutes) {
        $finishMinutes += 24 * 60;
    }

    if ($finishMinutes === $startMinutes) {
        return 0;
    }

    $totalMinutes = $finishMinutes - $startMinutes;

    // Kurangi waktu istirahat 12.00–13.00.
    for (
        $day = (int) floor($startMinutes / 1440);
        $day <= (int) floor($finishMinutes / 1440);
        $day++
    ) {
        $breakStart = $day * 1440 + 720;
        $breakFinish = $day * 1440 + 780;

        $overlapStart = max($startMinutes, $breakStart);
        $overlapFinish = min($finishMinutes, $breakFinish);

        if ($overlapFinish > $overlapStart) {
            $totalMinutes -= $overlapFinish - $overlapStart;
        }
    }

    return max(0, $totalMinutes / 60);
}

function validTime(string $time): bool
{
    return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
}

function getIssue(mysqli $conn, int $issueId): ?array
{
    $stmt = mysqli_prepare(
        $conn,
        'SELECT * FROM issue_targets WHERE id = ? LIMIT 1'
    );

    mysqli_stmt_bind_param($stmt, 'i', $issueId);
    mysqli_stmt_execute($stmt);
    $issue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $issue ?: null;
}

function updateIssueStatus(
    mysqli $conn,
    int $issueId,
    int $wrId,
    int $qtyTarget
): string {
    // Status Done tidak otomatis dibuka kembali ketika aktivitas diedit.
    $stmt = mysqli_prepare(
        $conn,
        'SELECT status FROM issue_targets WHERE id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $issueId);
    mysqli_stmt_execute($stmt);
    $current = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$current) {
        return 'Open';
    }

    if ($current['status'] === 'Done') {
        return 'Done';
    }

    $totals = getTotals($conn, $wrId);
    $totalQty = (int) $totals['total_qty'];

    if ($totalQty >= $qtyTarget) {
        $status = 'Done';
    } elseif ($totalQty > 0) {
        $status = 'Progress';
    } else {
        $status = 'Open';
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE issue_targets SET status = ? WHERE id = ?'
    );
    mysqli_stmt_bind_param($stmt, 'si', $status, $issueId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $status;
}

$issueId = filter_input(INPUT_GET, 'issue_id', FILTER_VALIDATE_INT);

if (!$issueId || $issueId < 1) {
    header(
        'Location: ' .
        ($role === 'admin' ? '../admin/issue.php' : 'issue.php')
    );
    exit;
}

$error = '';
$success = '';
$editActivity = null;

try {
    $issue = getIssue($conn, (int) $issueId);

    if (!$issue) {
        http_response_code(404);
        exit('Issue Target tidak ditemukan.');
    }

    // Cari Working Record yang terkait dengan issue.
    $stmt = mysqli_prepare(
        $conn,
        'SELECT * FROM working_records WHERE issue_id = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $issueId);
    mysqli_stmt_execute($stmt);
    $wr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Buat WR jika belum tersedia.
    if (!$wr) {
        $tanggal = $issue['tanggal_issue'];

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO working_records (issue_id, tanggal) VALUES (?, ?)'
        );
        mysqli_stmt_bind_param($stmt, 'is', $issueId, $tanggal);
        mysqli_stmt_execute($stmt);
        $wrId = (int) mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare(
            $conn,
            'SELECT * FROM working_records WHERE id = ? LIMIT 1'
        );
        mysqli_stmt_bind_param($stmt, 'i', $wrId);
        mysqli_stmt_execute($stmt);
        $wr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }

    if (!$wr) {
        throw new RuntimeException('Working Record gagal dibuat atau ditemukan.');
    }

    $wrId = (int) $wr['id'];

    // Proses edit aktivitas yang sudah ada.
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['edit_aktivitas'])
    ) {
        $detailId = filter_input(INPUT_POST, 'detail_id', FILTER_VALIDATE_INT);
        $start = trim($_POST['edit_start_time'] ?? '');
        $finish = trim($_POST['edit_finish_time'] ?? '');
        $qty = filter_input(INPUT_POST, 'edit_qty_hasil', FILTER_VALIDATE_INT);
        $remark = trim($_POST['edit_remark'] ?? '');

        if (!$detailId || $detailId < 1) {
            $error = 'Aktivitas yang akan diedit tidak valid.';
        } elseif ($qty === false || $qty === null || $qty < 0) {
            $error = 'Qty Hasil harus berupa angka nol atau lebih.';
        } elseif (strlen($remark) > 255) {
            $error = 'Remark maksimal 255 karakter.';
        } elseif (!validTime($start) || !validTime($finish)) {
            $error = 'Jam mulai atau jam selesai tidak valid.';
        } elseif ($start === $finish) {
            $error = 'Jam mulai dan jam selesai tidak boleh sama.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                'SELECT id
                 FROM working_record_details
                 WHERE id = ? AND wr_id = ?
                 LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $detailId, $wrId);
            mysqli_stmt_execute($stmt);
            $activity = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$activity) {
                $error = 'Aktivitas tidak ditemukan.';
            } else {
                $totalJam = calculateHours($start, $finish);
                $startDb = $start . ':00';
                $finishDb = $finish . ':00';

                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE working_record_details
                     SET qty_hasil = ?,
                         start_time = ?,
                         finish_time = ?,
                         total_jam = ?,
                         remark = ?
                     WHERE id = ? AND wr_id = ?'
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    'issdsii',
                    $qty,
                    $startDb,
                    $finishDb,
                    $totalJam,
                    $remark,
                    $detailId,
                    $wrId
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                header(
                    'Location: working_record.php?issue_id=' .
                    $issueId . '&saved=edited'
                );
                exit;
            }
        }

        if ($detailId && $detailId > 0) {
            $stmt = mysqli_prepare(
                $conn,
                'SELECT d.*, u.nama, u.nik
                 FROM working_record_details d
                 INNER JOIN users u ON u.NO = d.user_id
                 WHERE d.id = ? AND d.wr_id = ?
                 LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $detailId, $wrId);
            mysqli_stmt_execute($stmt);
            $editActivity = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
        }
    }

    // Proses tambah aktivitas.
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['simpan'])
    ) {
        // Karyawan tidak boleh menambah aktivitas jika status sudah Done.
        if (
            $role === 'karyawan'
            && ($issue['status'] ?? '') === 'Done'
        ) {
            $error = 'Working Record sudah Done/OK. Aktivitas baru tidak dapat ditambahkan.';
        } else {
            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $qty = filter_input(INPUT_POST, 'qty_hasil', FILTER_VALIDATE_INT);
            $start = trim($_POST['start_time'] ?? '');
            $finish = trim($_POST['finish_time'] ?? '');
            $remark = trim($_POST['remark'] ?? '');

            if (!$userId || $userId < 1) {
                $error = 'Silakan pilih operator.';
            } elseif ($qty === false || $qty === null || $qty < 0) {
                $error = 'Qty Hasil harus berupa angka nol atau lebih.';
            } elseif (strlen($remark) > 255) {
                $error = 'Remark maksimal 255 karakter.';
            } elseif (!validTime($start) || !validTime($finish)) {
                $error = 'Jam mulai atau jam selesai tidak valid.';
            } elseif ($start === $finish) {
                $error = 'Jam mulai dan jam selesai tidak boleh sama.';
            } else {
                // Tabel users pada database ini memakai kolom NO sebagai ID.
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT NO FROM users
                     WHERE NO = ? AND role = 'karyawan'
                     LIMIT 1"
                );
                mysqli_stmt_bind_param($stmt, 'i', $userId);
                mysqli_stmt_execute($stmt);
                $operator = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                mysqli_stmt_close($stmt);

                if (!$operator) {
                    $error = 'Operator tidak ditemukan.';
                } else {
                    $totalJam = calculateHours($start, $finish);
                    $startDb = $start . ':00';
                    $finishDb = $finish . ':00';

                    $stmt = mysqli_prepare(
                        $conn,
                        'INSERT INTO working_record_details
                         (wr_id, user_id, qty_hasil, start_time,
                          finish_time, total_jam, remark)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        'iiissds',
                        $wrId,
                        $userId,
                        $qty,
                        $startDb,
                        $finishDb,
                        $totalJam,
                        $remark
                    );

                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    updateIssueStatus(
                        $conn,
                        (int) $issueId,
                        $wrId,
                        (int) $issue['qty_target']
                    );

                    header(
                        'Location: working_record.php?issue_id=' .
                        $issueId . '&saved=1'
                    );
                    exit;
                }
            }
        }
    }

    // Proses tombol OK / selesaikan WR.
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && isset($_POST['selesaikan_wr'])
    ) {
        $totalsNow = getTotals($conn, $wrId);

        if ((int) $totalsNow['total_qty'] < (int) $issue['qty_target']) {
            $error = 'Qty hasil belum mencapai target. WR belum bisa diselesaikan.';
        } else {
            $statusDone = 'Done';

            $stmt = mysqli_prepare(
                $conn,
                'UPDATE issue_targets SET status = ? WHERE id = ?'
            );
            mysqli_stmt_bind_param($stmt, 'si', $statusDone, $issueId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header(
                'Location: working_record.php?issue_id=' .
                $issueId . '&saved=done'
            );
            exit;
        }
    }

    // Daftar operator. NO dipakai sebagai nilai ID.
    $operators = mysqli_query(
        $conn,
        "SELECT NO AS id, nik, nama
         FROM users
         WHERE role = 'karyawan'
         ORDER BY nama ASC"
    );

    // Buka form edit jika edit_id ada di URL.
    if ($editActivity === null && isset($_GET['edit_id'])) {
        $editId = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);

        if ($editId && $editId > 0) {
            $stmt = mysqli_prepare(
                $conn,
                'SELECT d.*, u.nama, u.nik
                 FROM working_record_details d
                 INNER JOIN users u ON u.NO = d.user_id
                 WHERE d.id = ? AND d.wr_id = ?
                 LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $editId, $wrId);
            mysqli_stmt_execute($stmt);
            $editActivity = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$editActivity) {
                $error = 'Aktivitas yang dipilih tidak ditemukan.';
            }
        }
    }

    // Ambil semua aktivitas.
    $stmt = mysqli_prepare(
        $conn,
        'SELECT d.*, u.nama, u.nik
         FROM working_record_details d
         INNER JOIN users u ON u.NO = d.user_id
         WHERE d.wr_id = ?
         ORDER BY d.id ASC'
    );
    mysqli_stmt_bind_param($stmt, 'i', $wrId);
    mysqli_stmt_execute($stmt);
    $details = mysqli_stmt_get_result($stmt);

    $totals = getTotals($conn, $wrId);
    $totalQty = (int) $totals['total_qty'];
    $qtyTarget = (int) $issue['qty_target'];
    $targetTercapai = $totalQty >= $qtyTarget;

    $status = updateIssueStatus(
        $conn,
        (int) $issueId,
        $wrId,
        $qtyTarget
    );

    $statusClass = $status === 'Done'
        ? 'status-done'
        : ($status === 'Progress' ? 'status-progress' : 'status-open');

    if (($_GET['saved'] ?? '') === 'done') {
        $success = 'OK! Working Record berhasil diselesaikan.';
    } elseif (($_GET['saved'] ?? '') === '1') {
        $success = 'Aktivitas berhasil disimpan.';
    } elseif (($_GET['saved'] ?? '') === 'edited') {
        $success = 'Aktivitas operator berhasil diperbarui.';
    }

    $wrIsDone = ($role === 'karyawan' && $status === 'Done');
    $namaLogin = $_SESSION['nama'] ?? 'Karyawan';

} catch (Throwable $ex) {
    error_log('Working Record error: ' . $ex->getMessage());
    http_response_code(500);
    exit(
        'Terjadi kesalahan saat memproses Working Record. ' .
        'Periksa struktur tabel database dan log PHP XAMPP.'
    );
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Working Record</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: Arial, sans-serif;
    background: #eef6fc;
    color: #263445;
    min-height: 100vh;
}
.header {
    background: linear-gradient(120deg, #d9364f, #e94c60);
    color: white;
    padding: 22px 30px;
}
.header h1 {
    font-size: 24px;
    margin-bottom: 6px;
}
.header p {
    font-size: 13px;
    line-height: 1.6;
}
.container {
    max-width: 1200px;
    margin: 24px auto;
    padding: 0 18px;
}
.navigation, .action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-bottom: 20px;
}
.action-buttons {
    margin: 0;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 14px;
    border: none;
    border-radius: 7px;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    font-family: inherit;
}
.btn-back, .btn-edit {
    background: #dff1ff;
    color: #245783;
}
.btn-logout, .btn-cancel {
    background: #fff0f1;
    color: #bd2941;
}
.btn-save {
    background: #d9364f;
    color: white;
}
.btn-save:hover {
    background: #bd2941;
}
.btn-complete {
    margin-top: 12px;
    padding: 11px 17px;
    background: #2185b5;
    color: white;
    border: none;
    border-radius: 7px;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
}
.page-heading {
    margin-bottom: 20px;
}
.page-heading h2 {
    color: #263f63;
    font-size: 23px;
    margin-bottom: 6px;
}
.page-heading p {
    color: #728096;
    font-size: 13px;
    line-height: 1.6;
}
.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 18px rgba(47,83,120,.07);
    border: 1px solid #e5edf4;
}
.card-title {
    color: #263f63;
    font-size: 17px;
    margin-bottom: 17px;
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.info-item {
    padding: 13px;
    border-radius: 8px;
    background: #f2f9ff;
    border: 1px solid #dcefff;
    overflow-wrap: anywhere;
}
.info-label {
    display: block;
    color: #728096;
    font-size: 11px;
    margin-bottom: 7px;
}
.info-value {
    color: #263f63;
    font-size: 13px;
    font-weight: bold;
}
.status {
    display: inline-block;
    padding: 6px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
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
.message {
    padding: 12px 14px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    line-height: 1.6;
}
.message-error {
    color: #a82337;
    background: #fff0f1;
    border: 1px solid #ffd1d8;
}
.message-success, .completion {
    color: #21618c;
    background: #e6f5ff;
    border: 1px solid #c6e7ff;
}
.completion {
    border-radius: 10px;
    padding: 17px;
    margin-bottom: 20px;
}
.completion h3 {
    margin-bottom: 7px;
    font-size: 17px;
}
.completion p {
    font-size: 13px;
    line-height: 1.6;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.form-group.full {
    grid-column: 1 / -1;
}
label {
    color: #354d69;
    font-size: 12px;
    font-weight: bold;
}
input, select {
    width: 100%;
    padding: 11px 12px;
    background: #fbfdff;
    border: 1px solid #cbdff0;
    border-radius: 7px;
    font-family: inherit;
    font-size: 13px;
    color: #263445;
}
input:focus, select:focus {
    outline: 2px solid #c6e7ff;
    border-color: #e24a60;
}
.table-wrapper {
    width: 100%;
    overflow-x: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}
thead {
    background: #dff1ff;
}
th {
    text-align: left;
    color: #244b70;
    font-size: 11px;
    padding: 12px 10px;
    border-bottom: 2px solid #c7e5fa;
    white-space: nowrap;
}
td {
    padding: 12px 10px;
    font-size: 12px;
    border-bottom: 1px solid #edf2f7;
    vertical-align: middle;
}
tbody tr:hover {
    background: #f4faff;
}
tbody tr:last-child td {
    border-bottom: none;
}
.empty {
    text-align: center;
    color: #7b8798;
    padding: 22px;
}
.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.summary-box {
    padding: 18px;
    border-radius: 10px;
    background: #f2f9ff;
    border: 1px solid #dcefff;
}
.summary-box.red {
    background: #fff2f4;
    border-color: #ffdae0;
}
.summary-label {
    color: #728096;
    font-size: 12px;
    margin-bottom: 8px;
}
.summary-value {
    color: #245783;
    font-size: 24px;
    font-weight: bold;
}
.summary-box.red .summary-value {
    color: #d9364f;
}
.summary-value small {
    color: #728096;
    font-size: 12px;
    font-weight: normal;
}
.footer {
    text-align: center;
    padding: 20px;
    color: #8090a3;
    font-size: 11px;
}
@media (max-width: 700px) {
    .header { padding: 20px; }
    .container { margin-top: 18px; padding: 0 10px; }
    .card { padding: 15px; }
    .info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .form-grid, .summary-grid { grid-template-columns: 1fr; }
    .form-group.full { grid-column: auto; }
}
</style>
</head>

<body>
<header class="header">
    <h1>Working Record</h1>
    <p>Selamat datang, <?= e($namaLogin) ?>.</p>
    <p>Sistem Pencatatan Working Record Operator PT EX</p>
</header>

<main class="container">
    <nav class="navigation">
        <?php if ($role === 'admin'): ?>
            <a href="../admin/issue.php" class="btn btn-back">Kembali ke Issue Target</a>
        <?php else: ?>
            <a href="issue.php" class="btn btn-back">Kembali ke Issue Target</a>
        <?php endif; ?>

        <a href="../logout.php" class="btn btn-logout"
           onclick="return confirm('Yakin ingin logout?')">Logout</a>
    </nav>

    <div class="page-heading">
        <h2>Working Record #<?= e($wrId) ?></h2>
        <p>Catat aktivitas operator, edit aktivitas yang sudah ada, dan pantau hasil produksi.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="message message-error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="message message-success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($targetTercapai && $status !== 'Done'): ?>
        <section class="completion">
            <h3>Target Qty Tercapai!</h3>
            <p>Total hasil: <strong><?= number_format($totalQty) ?> / <?= number_format($qtyTarget) ?></strong>.</p>
            <form method="POST" onsubmit="return confirm('Selesaikan Working Record ini?')">
                <button type="submit" name="selesaikan_wr" class="btn-complete">
                    OK / Selesaikan WR
                </button>
            </form>
        </section>
    <?php elseif ($status === 'Done'): ?>
        <section class="completion">
            <h3>Working Record sudah OK / Done</h3>
            <p>
                <?php if ($role === 'karyawan'): ?>
                    Aktivitas baru tidak dapat ditambahkan. Kamu masih dapat mengedit aktivitas yang sudah ada.
                <?php else: ?>
                    Status Working Record ini sudah Done.
                <?php endif; ?>
            </p>
        </section>
    <?php endif; ?>

    <section class="card">
        <h3 class="card-title">Informasi Issue Target</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Tanggal Issue</span>
                <span class="info-value"><?= e($issue['tanggal_issue']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Model</span>
                <span class="info-value"><?= e($issue['model']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Lot No</span>
                <span class="info-value"><?= e($issue['lot_no'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Line</span>
                <span class="info-value"><?= e($issue['line']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Qty Target</span>
                <span class="info-value"><?= number_format($qtyTarget) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="status <?= e($statusClass) ?>"><?= e($status) ?></span>
            </div>
        </div>
    </section>

    <?php if ($editActivity): ?>
        <section class="card" id="form-edit">
            <h3 class="card-title">Edit Aktivitas Operator</h3>

            <div class="info-item" style="margin-bottom:16px">
                <span class="info-label">Operator (tidak diubah)</span>
                <span class="info-value">
                    <?= e($editActivity['nama']) ?> (<?= e($editActivity['nik']) ?>)
                </span>
            </div>

            <form method="POST">
                <input type="hidden" name="detail_id" value="<?= (int) $editActivity['id'] ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_start_time">Jam Mulai</label>
                        <input type="time" id="edit_start_time" name="edit_start_time"
                            value="<?= e($_POST['edit_start_time'] ?? substr($editActivity['start_time'], 0, 5)) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_finish_time">Jam Selesai</label>
                        <input type="time" id="edit_finish_time" name="edit_finish_time"
                            value="<?= e($_POST['edit_finish_time'] ?? substr($editActivity['finish_time'], 0, 5)) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_qty_hasil">Qty Hasil</label>
                        <input type="number" id="edit_qty_hasil" name="edit_qty_hasil" min="0" step="1"
                            value="<?= e($_POST['edit_qty_hasil'] ?? $editActivity['qty_hasil']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_remark">Remark</label>
                        <input type="text" id="edit_remark" name="edit_remark" maxlength="255"
                            value="<?= e($_POST['edit_remark'] ?? $editActivity['remark']) ?>"
                            placeholder="Keterangan (opsional)">
                    </div>

                    <div class="form-group full">
                        <div class="action-buttons">
                            <button type="submit" name="edit_aktivitas" class="btn btn-save">Simpan Perubahan</button>
                            <a href="working_record.php?issue_id=<?= (int) $issueId ?>" class="btn btn-cancel">Batal</a>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <?php if (!$wrIsDone): ?>
        <section class="card">
            <h3 class="card-title">Tambah Aktivitas Operator</h3>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="user_id">Operator</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">-- Pilih Operator --</option>
                            <?php while ($op = mysqli_fetch_assoc($operators)): ?>
                                <option value="<?= (int) $op['id'] ?>"
                                    <?= (isset($_POST['user_id']) && (string) $_POST['user_id'] === (string) $op['id']) ? 'selected' : '' ?>>
                                    <?= e($op['nama']) ?> (<?= e($op['nik']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_time">Jam Mulai</label>
                        <input type="time" name="start_time" id="start_time"
                            value="<?= e($_POST['start_time'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="finish_time">Jam Selesai</label>
                        <input type="time" name="finish_time" id="finish_time"
                            value="<?= e($_POST['finish_time'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="qty_hasil">Qty Hasil</label>
                        <input type="number" name="qty_hasil" id="qty_hasil" min="0" step="1"
                            value="<?= e($_POST['qty_hasil'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="remark">Remark</label>
                        <input type="text" name="remark" id="remark" maxlength="255"
                            value="<?= e($_POST['remark'] ?? '') ?>" placeholder="Keterangan (opsional)">
                    </div>

                    <div class="form-group full">
                        <button type="submit" name="simpan" class="btn btn-save">+ Simpan Aktivitas</button>
                    </div>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="card">
            <h3 class="card-title">Tambah Aktivitas Operator</h3>
            <p>Working Record sudah Done. Form tambah aktivitas dinonaktifkan untuk karyawan.</p>
        </section>
    <?php endif; ?>

    <section class="card">
        <h3 class="card-title">Daftar Aktivitas Operator</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Operator</th>
                        <th>NIK</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Total Jam</th>
                        <th>Qty Hasil</th>
                        <th>Remark</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($details && mysqli_num_rows($details) > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($d = mysqli_fetch_assoc($details)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($d['nama']) ?></td>
                                <td><?= e($d['nik']) ?></td>
                                <td><?= e(substr($d['start_time'], 0, 5)) ?></td>
                                <td><?= e(substr($d['finish_time'], 0, 5)) ?></td>
                                <td><?= number_format((float) $d['total_jam'], 2) ?> jam</td>
                                <td><?= number_format((int) $d['qty_hasil']) ?></td>
                                <td><?= e($d['remark'] ?? '-') ?></td>
                                <td>
                                    <a class="btn btn-edit"
                                       href="working_record.php?issue_id=<?= (int) $issueId ?>&edit_id=<?= (int) $d['id'] ?>#form-edit">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty">Belum ada aktivitas operator.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h3 class="card-title">Rekap Working Record</h3>
        <div class="summary-grid">
            <div class="summary-box red">
                <div class="summary-label">Total Qty Hasil</div>
                <div class="summary-value">
                    <?= number_format($totalQty) ?>
                    <small>/ <?= number_format($qtyTarget) ?></small>
                </div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Total Jam Kerja</div>
                <div class="summary-value">
                    <?= number_format((float) $totals['total_jam'], 2) ?>
                    <small>jam</small>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="footer">Sistem Pencatatan Working Record Operator PT EX</footer>
</body>
</html>
