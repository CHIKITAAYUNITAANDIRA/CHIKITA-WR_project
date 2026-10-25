
<?php

require '../session_check.php';
require '../config/database.php';

if (isset($_POST['simpan']))
{
    $tanggal_issue = $_POST['tanggal_issue'];
    $model = $_POST['model'];
    $lot_no = $_POST['lot_no'];
    $line = $_POST['line'];
    $qty_target = $_POST['qty_target'];

    $created_by = $_SESSION['id'];

    mysqli_query($conn,"
        INSERT INTO issue_targets
        (
            tanggal_issue,
            model,
            lot_no,
            line,
            qty_target,
            status,
            created_by
        )
        VALUES
        (
            '$tanggal_issue',
            '$model',
            '$lot_no',
            '$line',
            '$qty_target',
            'Open',
            '$created_by'
        )
    ");

    header("Location: issue.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Issue - Working Record</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 25px 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, Helvetica, sans-serif;
            color: #243b53;
            background: #edf6fc;
        }

        .container {
            width: 100%;
            max-width: 650px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #dceaf4;
            box-shadow: 0 8px 25px rgba(37, 65, 91, 0.09);
        }

        .header {
            padding: 20px 25px;
            color: #ffffff;
            background: #e93636;
            border-bottom: 4px solid #b91f2b;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            font-size: 12px;
            line-height: 1.6;
            color: #fff0f0;
        }

        .content {
            padding: 24px 25px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 18px;
            padding: 9px 13px;
            border: 1px solid #c7e5fb;
            border-radius: 7px;
            background: #edf7ff;
            color: #21618c;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #dcefff;
            border-color: #a9d6f5;
        }

        .info {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            border: 1px solid #c7e5fb;
            border-left: 4px solid #278bd1;
            border-radius: 7px;
            background: #eef8ff;
            color: #235b83;
            font-size: 12px;
            line-height: 1.6;
        }

        .info-icon {
            flex-shrink: 0;
            font-size: 17px;
            font-weight: bold;
            color: #1681c4;
        }

        .info strong {
            display: block;
            margin-bottom: 2px;
            color: #174d75;
        }

        .form-group {
            margin-bottom: 17px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            color: #263b50;
            font-size: 13px;
            font-weight: 700;
        }

        .required {
            color: #e93636;
        }

        input {
            width: 100%;
            height: 42px;
            padding: 10px 12px;
            border: 1px solid #cfdeea;
            border-radius: 7px;
            background: #fbfdff;
            color: #263b50;
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: 0.2s;
        }

        input:focus {
            border-color: #268bd2;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(38, 139, 210, 0.10);
        }

        input::placeholder {
            color: #9aabba;
        }

        input[type="number"] {
            appearance: textfield;
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 1;
        }

        .help {
            display: block;
            margin-top: 5px;
            color: #7b8fa1;
            font-size: 11px;
            line-height: 1.5;
        }

        .divider {
            height: 1px;
            margin: 23px 0 18px;
            background: #e5edf4;
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            min-height: 41px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            border: 1px solid transparent;
            border-radius: 7px;
            text-decoration: none;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-save {
            flex: 1;
            color: #ffffff;
            background: #e93636;
            border-color: #e93636;
            box-shadow: 0 3px 8px rgba(233, 54, 54, 0.15);
        }

        .btn-save:hover {
            background: #c92730;
            border-color: #c92730;
        }

        .btn-cancel {
            color: #21618c;
            background: #edf7ff;
            border-color: #c7e5fb;
        }

        .btn-cancel:hover {
            background: #dcefff;
            border-color: #a9d6f5;
        }

        @media (max-width: 520px) {
            body {
                padding: 12px;
                align-items: flex-start;
            }

            .header {
                padding: 18px;
            }

            .header h1 {
                font-size: 20px;
            }

            .content {
                padding: 19px 18px;
            }

            .actions {
                flex-direction: column;
            }

            .btn-save,
            .btn-cancel {
                width: 100%;
                flex: auto;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>Tambah Issue</h1>
        <p>
            Lengkapi informasi target issue untuk pencatatan Working Record.
        </p>
    </div>

    <div class="content">

        <!-- Tombol kembali ke halaman sebelum Tambah Issue -->
        <a href="issue.php" class="btn-back">
            &larr; Kembali
        </a>

        <div class="info">
            <div class="info-icon">ⓘ</div>
            <div>
                <strong>Informasi Issue</strong>
                Isi tanggal issue, model, lot number, line, dan jumlah target.
                Status awal issue akan otomatis menjadi Open.
            </div>
        </div>

        <form method="POST">

            <div class="form-group">
                <label for="tanggal_issue">
                    Tanggal Issue <span class="required">*</span>
                </label>

                <input
                    type="date"
                    id="tanggal_issue"
                    name="tanggal_issue"
                    required
                >
            </div>

            <div class="form-group">
                <label for="model">
                    Model <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="model"
                    name="model"
                    maxlength="50"
                    placeholder="Masukkan nama atau kode model"
                    required
                >
            </div>

            <div class="form-group">
                <label for="lot_no">Lot No</label>

                <input
                    type="text"
                    id="lot_no"
                    name="lot_no"
                    maxlength="50"
                    placeholder="Masukkan nomor lot (opsional)"
                >

                <small class="help">
                    Lot No boleh dikosongkan jika tidak tersedia.
                </small>
            </div>

            <div class="form-group">
                <label for="line">
                    Line <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="line"
                    name="line"
                    maxlength="50"
                    placeholder="Contoh: Line 1"
                    required
                >
            </div>

            <div class="form-group">
                <label for="qty_target">
                    Qty Target <span class="required">*</span>
                </label>

                <input
                    type="number"
                    id="qty_target"
                    name="qty_target"
                    min="1"
                    step="1"
                    placeholder="Masukkan jumlah target"
                    required
                >

                <small class="help">
                    Masukkan jumlah unit yang menjadi target penyelesaian.
                </small>
            </div>

            <div class="divider"></div>

            <div class="actions">
                <button
                    type="submit"
                    name="simpan"
                    class="btn btn-save"
                >
                    ✓ Simpan Issue
                </button>

                <a href="issue.php" class="btn btn-cancel">
                    Batal
                </a>
            </div>

        </form>

    </div>

</div>

</body>
</html>
