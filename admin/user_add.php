```php
<?php
require '../session_check.php';
require '../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$error = '';

if (isset($_POST['simpan'])) {
    $nik = trim($_POST['nik'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($nik === '' || $nama === '') {
        $error = 'NIK dan nama wajib diisi.';
    } elseif (!in_array($role, ['admin', 'karyawan'], true)) {
        $error = 'Role yang dipilih tidak valid.';
    } else {
        $password = password_hash('123456', PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO users (nik, nama, password, role)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $nik,
            $nama,
            $password,
            $role
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: users.php");
            exit;
        }

        if (mysqli_stmt_errno($stmt) === 1062) {
            $error = 'NIK tersebut sudah terdaftar. Gunakan NIK lain.';
        } else {
            $error = 'User gagal ditambahkan. Silakan coba lagi.';
        }

        mysqli_stmt_close($stmt);
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah User | Working Record PT EX</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top left, #d9efff, transparent 38%),
                radial-gradient(circle at bottom right, #ffe0e4, transparent 35%),
                #f1f8ff;
            color: #263b55;
            padding: 30px 18px;
        }

        .container {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: #e53945;
            color: white;
            font-size: 21px;
            font-weight: 800;
        }

        .brand h1 {
            color: #173c68;
            font-size: 19px;
        }

        .brand p {
            color: #8091a6;
            font-size: 12px;
            margin-top: 4px;
        }

        .back-top {
            padding: 10px 15px;
            border-radius: 9px;
            background: #e3f2ff;
            border: 1px solid #c8e5ff;
            color: #2675bb;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: .2s;
        }

        .back-top:hover {
            background: #d2eaff;
        }

        .form-card {
            background: #ffffff;
            border: 1px solid #e1ebf5;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 14px 40px rgba(42, 77, 116, .09);
        }

        .card-header {
            padding: 27px 32px;
            color: #ffffff;
            background: linear-gradient(110deg, #d9364f, #f15b70);
        }

        .header-label {
            display: inline-block;
            padding: 6px 10px;
            margin-bottom: 12px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .18);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .card-header h2 {
            font-size: 25px;
            margin-bottom: 8px;
        }

        .card-header p {
            color: #fff0f2;
            font-size: 13px;
            line-height: 1.7;
        }

        .form-body {
            padding: 30px 32px;
        }

        .info-box {
            display: flex;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 25px;
            border: 1px solid #cfe9ff;
            border-radius: 11px;
            background: #eef8ff;
            color: #37688f;
            font-size: 12px;
            line-height: 1.7;
        }

        .info-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .error-box {
            padding: 14px 16px;
            margin-bottom: 22px;
            border: 1px solid #ffcbd2;
            border-radius: 10px;
            background: #fff0f2;
            color: #bb293e;
            font-size: 13px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #304967;
            font-size: 13px;
            font-weight: 700;
        }

        .required {
            color: #e53945;
        }

        .form-group input,
        .form-group select {
            display: block;
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #d7e5f1;
            border-radius: 10px;
            outline: none;
            background: #f9fcff;
            color: #263b55;
            font-family: inherit;
            font-size: 14px;
            transition: .2s;
        }

        .form-group input::placeholder {
            color: #a1afbf;
        }

        .form-group input:focus,
        .form-group select:focus {
            background: #ffffff;
            border-color: #69b8e8;
            box-shadow: 0 0 0 4px rgba(105, 184, 232, .15);
        }

        .field-hint {
            margin-top: 7px;
            color: #8998aa;
            font-size: 11px;
            line-height: 1.6;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 22px;
            border-top: 1px solid #edf1f6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 47px;
            padding: 0 22px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: .2s;
        }

        .btn-save {
            flex: 1;
            border: none;
            color: #ffffff;
            background: linear-gradient(100deg, #d9364f, #f05269);
            box-shadow: 0 6px 15px rgba(217, 54, 79, .18);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 9px 19px rgba(217, 54, 79, .25);
        }

        .btn-cancel {
            border: 1px solid #cfe5f7;
            color: #3979ae;
            background: #edf7ff;
        }

        .btn-cancel:hover {
            background: #dcefff;
        }

        .footer {
            margin-top: 23px;
            color: #95a2b1;
            font-size: 11px;
            text-align: center;
        }

        @media (max-width: 520px) {
            body {
                padding: 20px 12px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .card-header {
                padding: 24px 21px;
            }

            .card-header h2 {
                font-size: 22px;
            }

            .form-body {
                padding: 24px 20px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <header class="topbar">
        <div class="brand">
            <div class="brand-icon">WR</div>
            <div>
                <h1>WORKING RECORD</h1>
                <p>Operator Management System · PT EX</p>
            </div>
        </div>

        <a href="users.php" class="back-top">
            &larr; Kembali ke Data User
        </a>
    </header>

    <main class="form-card">

        <div class="card-header">
            <span class="header-label">MANAJEMEN PENGGUNA</span>
            <h2>Tambah User Baru</h2>
            <p>Lengkapi data berikut untuk membuat akun pengguna baru.</p>
        </div>

        <div class="form-body">

            <div class="info-box">
                <span class="info-icon">&#9432;</span>
                <div>
                    <strong>Informasi password awal</strong><br>
                    Password awal untuk akun baru adalah
                    <strong>123456</strong>. Pengguna dapat masuk
                    menggunakan NIK dan password tersebut.
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="error-box">
                    <strong>Gagal menyimpan data</strong><br>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="nik">
                        NIK <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="nik"
                        name="nik"
                        maxlength="20"
                        placeholder="Contoh: 178 atau ADMIN001"
                        value="<?= e($_POST['nik'] ?? '') ?>"
                        autocomplete="off"
                        required
                    >

                    <p class="field-hint">
                        NIK harus unik dan digunakan untuk login.
                    </p>
                </div>

                <div class="form-group">
                    <label for="nama">
                        Nama Lengkap <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="nama"
                        name="nama"
                        maxlength="100"
                        placeholder="Masukkan nama lengkap"
                        value="<?= e($_POST['nama'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="role">
                        Role Pengguna <span class="required">*</span>
                    </label>

                    <select id="role" name="role" required>
                        <option
                            value="karyawan"
                            <?= (($_POST['role'] ?? 'karyawan') === 'karyawan') ? 'selected' : '' ?>
                        >
                            Karyawan
                        </option>
<!--  -->
                        <option
                            value="admin"
                            <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>
                        >
                            Admin
                        </option>
                    </select>

                    <p class="field-hint">
                        Admin dapat mengelola data pengguna dan issue target.
                    </p>
                </div>

                <div class="form-actions">
                    <button
                        type="submit"
                        name="simpan"
                        class="btn btn-save"
                    >
                        &#10003; Simpan User
                    </button>

                    <a href="users.php" class="btn btn-cancel">
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </main>

    <footer class="footer">
        &copy; <?= date('Y') ?> Working Record Operator PT EX
    </footer>

</div>

</body>
</html>
```