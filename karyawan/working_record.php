

<?php

require '../session_check.php';

require '../config/database.php';



$role = $_SESSION['role'] ?? '';



if (!in_array($role, ['admin', 'karyawan'], true)) {

    header("Location: ../login.php");

    exit;

}



function e($value) {

    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

}



function getTotals($conn, $wr_id) {

    $stmt = mysqli_prepare($conn, "

        SELECT

            COALESCE(SUM(qty_hasil), 0) AS total_qty,

            COALESCE(SUM(total_jam), 0) AS total_jam

        FROM working_record_details

        WHERE wr_id = ?

    ");

    mysqli_stmt_bind_param($stmt, "i", $wr_id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $totals = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);



    return $totals;

}



function calculateHours($start, $finish) {
    // Ubah jam menjadi jumlah menit sejak awal hari.
    $start_parts = array_map('intval', explode(':', $start));
    $finish_parts = array_map('intval', explode(':', $finish));

    $start_minutes = ($start_parts[0] * 60) + $start_parts[1];
    $finish_minutes = ($finish_parts[0] * 60) + $finish_parts[1];

    // Jika jam selesai lebih kecil dari jam mulai, aktivitas melewati tengah malam.
    if ($finish_minutes < $start_minutes) {
        $finish_minutes += 24 * 60;
    }

    $total_minutes = $finish_minutes - $start_minutes;

    // Kurangi hanya waktu yang bertumpang tindih dengan break 12:00–13:00.
    // Mendukung aktivitas yang melewati tengah malam.
    $first_day = (int) floor($start_minutes / (24 * 60));
    $last_day = (int) floor($finish_minutes / (24 * 60));

    for ($day = $first_day; $day <= $last_day; $day++) {
        $break_start = ($day * 24 * 60) + (12 * 60);
        $break_finish = ($day * 24 * 60) + (13 * 60);

        $overlap_start = max($start_minutes, $break_start);
        $overlap_finish = min($finish_minutes, $break_finish);

        if ($overlap_finish > $overlap_start) {
            $total_minutes -= ($overlap_finish - $overlap_start);
        }
    }

    return max(0, $total_minutes / 60);
}

function updateIssueStatus($conn, $issue_id, $wr_id, $qty_target) {

    $totals = getTotals($conn, $wr_id);

    $total_qty = (int)$totals['total_qty'];



    if ($total_qty >= (int)$qty_target) {

        $status = 'Done';

    } elseif ($total_qty > 0) {

        $status = 'Progress';

    } else {

        $status = 'Open';

    }



    $stmt = mysqli_prepare(

        $conn,

        "UPDATE issue_targets SET status = ? WHERE id = ?"

    );

    mysqli_stmt_bind_param($stmt, "si", $status, $issue_id);

    mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);



    return $status;

}



$issue_id = filter_input(INPUT_GET, 'issue_id', FILTER_VALIDATE_INT);



if (!$issue_id || $issue_id < 1) {

    header("Location: " . ($role === 'admin'

        ? '../admin/issue.php'

        : 'dashboard.php'));

    exit;

}



$error = '';

$success = '';

$edit_activity = null;



// Ambil data issue

$stmt = mysqli_prepare(

    $conn,

    "SELECT * FROM issue_targets WHERE id = ?"

);

mysqli_stmt_bind_param($stmt, "i", $issue_id);

mysqli_stmt_execute($stmt);

$issue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

mysqli_stmt_close($stmt);



if (!$issue) {

    http_response_code(404);

    exit('Issue Target tidak ditemukan.');

}



// Ambil atau buat Working Record

$stmt = mysqli_prepare(

    $conn,

    "SELECT * FROM working_records WHERE issue_id = ? LIMIT 1"

);

mysqli_stmt_bind_param($stmt, "i", $issue_id);

mysqli_stmt_execute($stmt);

$wr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

mysqli_stmt_close($stmt);



if (!$wr) {

    $tanggal = $issue['tanggal_issue'];



    $stmt = mysqli_prepare(

        $conn,

        "INSERT INTO working_records (issue_id, tanggal) VALUES (?, ?)"

    );

    mysqli_stmt_bind_param($stmt, "is", $issue_id, $tanggal);



    if (!mysqli_stmt_execute($stmt)) {

        exit('Gagal membuat Working Record. Periksa struktur tabel database.');

    }



    $wr_id = (int)mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);



    $stmt = mysqli_prepare(

        $conn,

        "SELECT * FROM working_records WHERE id = ?"

    );

    mysqli_stmt_bind_param($stmt, "i", $wr_id);

    mysqli_stmt_execute($stmt);

    $wr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    mysqli_stmt_close($stmt);

}



$wr_id = (int)$wr['id'];



/* =========================================

   PROSES EDIT AKTIVITAS OPERATOR

\========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'

    && isset($_POST['edit_aktivitas'])) {



    $detail_id = filter_input(INPUT_POST, 'detail_id', FILTER_VALIDATE_INT);

    $start = trim($_POST['edit_start_time'] ?? '');

    $finish = trim($_POST['edit_finish_time'] ?? '');

    $qty = filter_input(INPUT_POST, 'edit_qty_hasil', FILTER_VALIDATE_INT);

    $remark = trim($_POST['edit_remark'] ?? '');



    if (!$detail_id || $detail_id < 1) {

        $error = 'Aktivitas yang akan diedit tidak valid.';

    } elseif ($qty === false || $qty === null || $qty < 0) {

        $error = 'Qty Hasil harus berupa angka nol atau lebih.';

    } elseif (strlen($remark) > 255) {

        $error = 'Remark maksimal 255 karakter.';

    } elseif (

        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) ||

        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $finish)

    ) {

        $error = 'Jam mulai atau selesai tidak valid.';

    } else {

        // Pastikan aktivitas milik Working Record ini

        $stmt = mysqli_prepare(

            $conn,

            "SELECT id FROM working_record_details

             WHERE id = ? AND wr_id = ?"

        );

        mysqli_stmt_bind_param($stmt, "ii", $detail_id, $wr_id);

        mysqli_stmt_execute($stmt);

        $activity = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        mysqli_stmt_close($stmt);



        if (!$activity) {

            $error = 'Aktivitas tidak ditemukan.';

        } else {

            $total_jam = calculateHours($start, $finish);

            $start_db = $start . ':00';

            $finish_db = $finish . ':00';



            $stmt = mysqli_prepare(

                $conn,

                "UPDATE working_record_details

                 SET qty_hasil = ?,

                     start_time = ?,

                     finish_time = ?,

                     total_jam = ?,

                     remark = ?

                 WHERE id = ? AND wr_id = ?"

            );



            mysqli_stmt_bind_param(

                $stmt,

                "issdsii",

                $qty,

                $start_db,

                $finish_db,

                $total_jam,

                $remark,

                $detail_id,

                $wr_id

            );



            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);



                updateIssueStatus(

                    $conn,

                    $issue_id,

                    $wr_id,

                    $issue['qty_target']

                );



                header(

                    "Location: working_record.php?issue_id="

                    . $issue_id . "&saved=edited"

                );

                exit;

            } else {

                $error = 'Gagal mengedit aktivitas operator.';

                mysqli_stmt_close($stmt);

            }

        }

    }



    // Jika validasi gagal, tampilkan lagi form edit

    if ($detail_id && $detail_id > 0) {

        $stmt = mysqli_prepare(

            $conn,

            "SELECT d.*, u.nama, u.nik

             FROM working_record_details d

             INNER JOIN users u ON d.user_id = u.id

             WHERE d.id = ? AND d.wr_id = ?"

        );

        mysqli_stmt_bind_param($stmt, "ii", $detail_id, $wr_id);

        mysqli_stmt_execute($stmt);

        $edit_activity = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        mysqli_stmt_close($stmt);

    }

}



/* =========================================

   PROSES TAMBAH AKTIVITAS OPERATOR

\========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'

    && isset($_POST['simpan'])) {



    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    $qty = filter_input(INPUT_POST, 'qty_hasil', FILTER_VALIDATE_INT);

    $start = trim($_POST['start_time'] ?? '');

    $finish = trim($_POST['finish_time'] ?? '');

    $remark = trim($_POST['remark'] ?? '');



    if (!$user_id || $user_id < 1) {

        $error = 'Silakan pilih operator.';

    } elseif ($qty === false || $qty === null || $qty < 0) {

        $error = 'Qty Hasil harus berupa angka nol atau lebih.';

    } elseif (strlen($remark) > 255) {

        $error = 'Remark maksimal 255 karakter.';

    } elseif (

        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) ||

        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $finish)

    ) {

        $error = 'Jam mulai atau selesai tidak valid.';

    } else {

        $stmt = mysqli_prepare(

            $conn,

            "SELECT id FROM users WHERE id = ? AND role = 'karyawan'"

        );

        mysqli_stmt_bind_param($stmt, "i", $user_id);

        mysqli_stmt_execute($stmt);

        $operator = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        mysqli_stmt_close($stmt);



        if (!$operator) {

            $error = 'Operator tidak ditemukan.';

        } else {

            $total_jam = calculateHours($start, $finish);

            $start_db = $start . ':00';

            $finish_db = $finish . ':00';



            $stmt = mysqli_prepare(

                $conn,

                "INSERT INTO working_record_details

                 (wr_id, user_id, qty_hasil, start_time,

                  finish_time, total_jam, remark)

                 VALUES (?, ?, ?, ?, ?, ?, ?)"

            );



            mysqli_stmt_bind_param(

                $stmt,

                "iiissds",

                $wr_id,

                $user_id,

                $qty,

                $start_db,

                $finish_db,

                $total_jam,

                $remark

            );



            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);



                updateIssueStatus(

                    $conn,

                    $issue_id,

                    $wr_id,

                    $issue['qty_target']

                );



                header(

                    "Location: working_record.php?issue_id="

                    . $issue_id . "&saved=1"

                );

                exit;

            } else {

                $error = 'Gagal menyimpan aktivitas operator.';

                mysqli_stmt_close($stmt);

            }

        }

    }

}



/* =========================================

   PROSES OK / SELESAIKAN WR

\========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'

    && isset($_POST['selesaikan_wr'])) {



    $totals_now = getTotals($conn, $wr_id);



    if ((int)$totals_now['total_qty'] < (int)$issue['qty_target']) {

        $error = 'Qty hasil belum mencapai target. WR belum bisa diselesaikan.';

    } else {

        $status_done = 'Done';



        $stmt = mysqli_prepare(

            $conn,

            "UPDATE issue_targets SET status = ? WHERE id = ?"

        );

        mysqli_stmt_bind_param($stmt, "si", $status_done, $issue_id);



        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            header(

                "Location: working_record.php?issue_id="

                . $issue_id . "&saved=done"

            );

            exit;

        } else {

            $error = 'Gagal memperbarui status issue.';

            mysqli_stmt_close($stmt);

        }

    }

}



/* =========================================

   AMBIL DATA UNTUK TAMPILAN

\========================================= */



// Operator

$operators = mysqli_query(

    $conn,

    "SELECT id, nik, nama FROM users

     WHERE role = 'karyawan'

     ORDER BY nama ASC"

);



// Jika ada edit_id di URL, ambil aktivitas yang akan diedit

if ($edit_activity === null && isset($_GET['edit_id'])) {

    $edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);



    if ($edit_id && $edit_id > 0) {

        $stmt = mysqli_prepare(

            $conn,

            "SELECT d.*, u.nama, u.nik

             FROM working_record_details d

             INNER JOIN users u ON d.user_id = u.id

             WHERE d.id = ? AND d.wr_id = ?"

        );

        mysqli_stmt_bind_param($stmt, "ii", $edit_id, $wr_id);

        mysqli_stmt_execute($stmt);

        $edit_activity = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        mysqli_stmt_close($stmt);



        if (!$edit_activity) {

            $error = 'Aktivitas yang dipilih tidak ditemukan.';

        }

    }

}



// Daftar aktivitas

$stmt = mysqli_prepare(

    $conn,

    "SELECT d.*, u.nama, u.nik

     FROM working_record_details d

     INNER JOIN users u ON d.user_id = u.id

     WHERE d.wr_id = ?

     ORDER BY d.id ASC"

);

mysqli_stmt_bind_param($stmt, "i", $wr_id);

mysqli_stmt_execute($stmt);

$details = mysqli_stmt_get_result($stmt);



// Rekap dan status

$totals = getTotals($conn, $wr_id);

$total_qty = (int)$totals['total_qty'];

$qty_target = (int)$issue['qty_target'];

$target_tercapai = ($total_qty >= $qty_target);



$status = updateIssueStatus(

    $conn,

    $issue_id,

    $wr_id,

    $qty_target

);



$status_class = $status === 'Done'

    ? 'status-done'

    : ($status === 'Progress' ? 'status-progress' : 'status-open');



if (isset($_GET['saved']) && $_GET['saved'] === 'done') {

    $success = 'OK! Working Record berhasil diselesaikan.';

} elseif (isset($_GET['saved']) && $_GET['saved'] === '1') {

    $success = 'Aktivitas berhasil disimpan.';

} elseif (isset($_GET['saved']) && $_GET['saved'] === 'edited') {

    $success = 'Aktivitas operator berhasil diperbarui.';

}



$nama_login = $_SESSION['nama'] ?? 'Karyawan';



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

    box-shadow: 0 4px 14px rgba(120,35,55,.12);

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



.navigation {

    display: flex;

    flex-wrap: wrap;

    gap: 9px;

    margin-bottom: 20px;

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



.btn-back {

    background: #dff1ff;

    color: #245783;

}



.btn-logout {

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



.btn-edit {

    background: #dff1ff;

    color: #21618c;

}



.btn-edit:hover {

    background: #c7e7fc;

}



.btn-cancel {

    background: #fff0f1;

    color: #bd2941;

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



.message-success {

    color: #21618c;

    background: #e6f5ff;

    border: 1px solid #c6e7ff;

}



.completion {

    background: #e6f7ff;

    border: 1px solid #9edcff;

    border-radius: 10px;

    padding: 17px;

    margin-bottom: 20px;

}



.completion h3 {

    color: #21618c;

    margin-bottom: 7px;

    font-size: 17px;

}



.completion p {

    color: #385b78;

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

    outline: none;

}



input:focus, select:focus {

    border-color: #e24a60;

    box-shadow: 0 0 0 3px rgba(226,74,96,.09);

}



.table-wrapper {

    width: 100%;

    overflow-x: auto;

}



table {

    width: 100%;

    border-collapse: collapse;

    min-width: 900px;

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



.action-buttons {

    display: flex;

    flex-wrap: wrap;

    gap: 6px;

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

    .header {

        padding: 20px;

    }



    .container {

        margin-top: 18px;

        padding: 0 10px;

    }



    .card {

        padding: 15px;

    }



    .info-grid {

        grid-template-columns: repeat(2, minmax(0, 1fr));

    }



    .form-grid,

    .summary-grid {

        grid-template-columns: 1fr;

    }



    .form-group.full {

        grid-column: auto;

    }

}

</style>

</head>



<body>



<header class="header">

    <h1>Working Record</h1>

    <p>Selamat datang, <?= e($nama_login) ?>.</p>

    <p>Sistem Pencatatan Working Record Operator PT EX</p>

</header>



<main class="container">



    <nav class="navigation">

        <?php if ($role === 'admin'): ?>

            <a href="../admin/issue.php" class="btn btn-back">

                ← Kembali ke Issue Target

            </a>

        <?php else: ?>

            <a href="dashboard.php" class="btn btn-back">

                ← Kembali ke Dashboard

            </a>

        <?php endif; ?>



        <a href="../logout.php" class="btn btn-logout"

           onclick="return confirm('Yakin ingin logout?')">

            Logout

        </a>

    </nav>



    <div class="page-heading">

        <h2>Working Record #<?= e($wr_id) ?></h2>

        <p>Catat aktivitas operator, edit data aktivitas, dan pantau hasil produksi.</p>

    </div>



    <?php if ($error !== ''): ?>

        <div class="message message-error"><?= e($error) ?></div>

    <?php endif; ?>



    <?php if ($success !== ''): ?>

        <div class="message message-success"><?= e($success) ?></div>

    <?php endif; ?>



    <?php if ($target_tercapai): ?>

        <section class="completion">

            <h3>✓ Target Qty Tercapai!</h3>

            <p>

                Total hasil sudah mencapai

                <strong><?= number_format($total_qty) ?> /

                    <?= number_format($qty_target) ?></strong>.

                Working Record siap ditandai OK.

            </p>



            <form method="POST"

                  onsubmit="return confirm('Selesaikan Working Record ini?')">

                <button type="submit" name="selesaikan_wr"

                        class="btn-complete">

                    ✓ OK / Selesaikan WR

                </button>

            </form>

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

                <span class="info-value"><?= number_format($qty_target) ?></span>

            </div>



            <div class="info-item">

                <span class="info-label">Status</span>

                <span class="status <?= e($status_class) ?>">

                    <?= e($status) ?>

                </span>

            </div>

        </div>

    </section>



    <?php if ($edit_activity): ?>

        <section class="card" id="form-edit">

            <h3 class="card-title">Edit Aktivitas Operator</h3>



            <div class="info-item" style="margin-bottom:16px;">

                <span class="info-label">Operator (tidak diubah)</span>

                <span class="info-value">

                    <?= e($edit_activity['nama']) ?>

                    (<?= e($edit_activity['nik']) ?>)

                </span>

            </div>



            <form method="POST">

                <input type="hidden" name="detail_id"

                       value="<?= e($edit_activity['id']) ?>">



                <div class="form-grid">

                    <div class="form-group">

                        <label for="edit_start_time">Jam Mulai</label>

                        <input type="time" id="edit_start_time"

                               name="edit_start_time"

                               value="<?= e(

                                   $_POST['edit_start_time']

                                   ?? substr($edit_activity['start_time'], 0, 5)

                               ) ?>" required>

                    </div>



                    <div class="form-group">

                        <label for="edit_finish_time">Jam Selesai</label>

                        <input type="time" id="edit_finish_time"

                               name="edit_finish_time"

                               value="<?= e(

                                   $_POST['edit_finish_time']

                                   ?? substr($edit_activity['finish_time'], 0, 5)

                               ) ?>" required>

                    </div>



                    <div class="form-group">

                        <label for="edit_qty_hasil">Qty Hasil</label>

                        <input type="number" id="edit_qty_hasil"

                               name="edit_qty_hasil" min="0" step="1"

                               value="<?= e(

                                   $_POST['edit_qty_hasil']

                                   ?? $edit_activity['qty_hasil']

                               ) ?>" required>

                    </div>



                    <div class="form-group">

                        <label for="edit_remark">Remark</label>

                        <input type="text" id="edit_remark"

                               name="edit_remark" maxlength="255"

                               value="<?= e(

                                   $_POST['edit_remark']

                                   ?? $edit_activity['remark']

                               ) ?>"

                               placeholder="Keterangan (opsional)">

                    </div>



                    <div class="form-group full">

                        <div class="action-buttons">

                            <button type="submit" name="edit_aktivitas"

                                    class="btn btn-save">

                                Simpan Perubahan

                            </button>



                            <a href="working_record.php?issue_id=<?= $issue_id ?>"

                               class="btn btn-cancel">

                                Batal

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </section>

    <?php endif; ?>



    <section class="card">

        <h3 class="card-title">Tambah Aktivitas Operator</h3>



        <form method="POST">

            <div class="form-grid">

                <div class="form-group full">

                    <label for="user_id">Operator</label>

                    <select name="user_id" id="user_id" required>

                        <option value="">-- Pilih Operator --</option>

                        <?php if ($operators): ?>

                            <?php while ($op = mysqli_fetch_assoc($operators)): ?>

                                <option value="<?= e($op['id']) ?>"

                                    <?= (

                                        isset($_POST['user_id']) &&

                                        (string)$_POST['user_id'] === (string)$op['id']

                                    ) ? 'selected' : '' ?>>

                                    <?= e($op['nama']) ?> (<?= e($op['nik']) ?>)

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

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

                    <input type="number" name="qty_hasil" id="qty_hasil"

                           min="0" step="1"

                           value="<?= e($_POST['qty_hasil'] ?? '') ?>" required>

                </div>



                <div class="form-group">

                    <label for="remark">Remark</label>

                    <input type="text" name="remark" id="remark" maxlength="255"

                           value="<?= e($_POST['remark'] ?? '') ?>"

                           placeholder="Keterangan (opsional)">

                </div>



                <div class="form-group full">

                    <button type="submit" name="simpan" class="btn btn-save">

                        + Simpan Aktivitas

                    </button>

                </div>

            </div>

        </form>

    </section>



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

                                <td><?= e($d['start_time']) ?></td>

                                <td><?= e($d['finish_time']) ?></td>

                                <td>

                                    <?= number_format((float)$d['total_jam'], 2) ?>

                                    jam

                                </td>

                                <td><?= number_format((int)$d['qty_hasil']) ?></td>

                                <td><?= e($d['remark'] ?? '-') ?></td>

                                <td>

                                    <div class="action-buttons">

                                        <a class="btn btn-edit"

                                           href="working_record.php?issue_id=<?= $issue_id ?>&edit_id=<?= (int)$d['id'] ?>#form-edit">

                                            ✎ Edit

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>



                    <?php else: ?>

                        <tr>

                            <td colspan="9" class="empty">

                                Belum ada aktivitas operator.

                            </td>

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

                    <?= number_format($total_qty) ?>

                    <small>/ <?= number_format($qty_target) ?></small>

                </div>

            </div>



            <div class="summary-box">

                <div class="summary-label">Total Jam Kerja</div>

                <div class="summary-value">

                    <?= number_format((float)$totals['total_jam'], 2) ?>

                    <small>jam</small>

                </div>

            </div>

        </div>

    </section>



</main>



<footer class="footer">

    Sistem Pencatatan Working Record Operator PT EX

</footer>



</body>

</html>

