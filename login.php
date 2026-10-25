
<?php
session_start();

if (isset($_SESSION['id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: karyawan/dashboard.php");
        exit;
    }
}

$error = isset($_GET['error']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Working Record | PT EX</title>

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
                radial-gradient(circle at top left, #d8f0ff 0, transparent 38%),
                radial-gradient(circle at bottom right, #ffdfe5 0, transparent 35%),
                #f1f8ff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 950px;
            min-height: 560px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(47, 83, 120, .15);
        }

        /* Panel kiri */
        .welcome-panel {
            position: relative;
            overflow: hidden;
            padding: 48px 38px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
            background: linear-gradient(145deg, #d9364f 0%, #f45b70 100%);
        }

        .welcome-panel::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,.09);
            top: -100px;
            right: -90px;
        }

        .welcome-panel::after {
            content: "";
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
            bottom: -80px;
            left: -70px;
        }

        .brand {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 55px;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.28);
            font-size: 24px;
            font-weight: bold;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 700;
            letter-spacing: .4px;
        }

        .brand-subtitle {
            font-size: 12px;
            margin-top: 4px;
            opacity: .85;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .eyebrow {
            display: inline-block;
            padding: 8px 12px;
            margin-bottom: 20px;
            border-radius: 30px;
            background: rgba(255,255,255,.15);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
        }

        .welcome-content h1 {
            font-size: 35px;
            line-height: 1.25;
            margin-bottom: 18px;
        }

        .welcome-content p {
            font-size: 14px;
            line-height: 1.9;
            color: rgba(255,255,255,.9);
            max-width: 350px;
        }

        .welcome-footer {
            position: relative;
            z-index: 1;
            margin-top: 55px;
            font-size: 12px;
            color: rgba(255,255,255,.8);
        }

        /* Panel kanan */
        .login-panel {
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-heading {
            margin-bottom: 30px;
        }

        .small-title {
            color: #d9364f;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .login-heading h2 {
            color: #263f63;
            font-size: 29px;
            margin-bottom: 10px;
        }

        .login-heading p {
            color: #8190a3;
            font-size: 13px;
            line-height: 1.7;
        }

        .error-message {
            display: flex;
            gap: 10px;
            padding: 13px 14px;
            margin-bottom: 22px;
            border: 1px solid #ffd0d8;
            border-radius: 10px;
            background: #fff0f2;
            color: #bd2941;
            font-size: 13px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            color: #354d69;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: #8ba6be;
            font-size: 17px;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            height: 49px;
            padding: 0 15px 0 44px;
            border: 1px solid #d7e5f1;
            border-radius: 10px;
            outline: none;
            background: #f9fcff;
            color: #263445;
            font-family: inherit;
            font-size: 14px;
            transition: .2s;
        }

        .form-group input::placeholder {
            color: #a2afbd;
        }

        .form-group input:focus {
            background: #fff;
            border-color: #6dbbe9;
            box-shadow: 0 0 0 4px rgba(109,187,233,.15);
        }

        .password-input {
            padding-right: 48px !important;
        }

        /* Tombol ikon mata */
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: #6485a3;
            cursor: pointer;
            transition: .2s;
        }

        .password-toggle:hover {
            color: #d9364f;
            background: #fff0f2;
        }

        .password-toggle:focus-visible {
            outline: 2px solid #6dbbe9;
            outline-offset: 2px;
        }

        .password-toggle svg {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .login-button {
            width: 100%;
            height: 50px;
            margin-top: 6px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(100deg, #d9364f, #f05269);
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(217,54,79,.2);
            transition: transform .2s, box-shadow .2s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 11px 22px rgba(217,54,79,.28);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 22px;
            color: #8292a5;
            font-size: 11px;
            text-align: center;
            line-height: 1.6;
        }

        .security-note span {
            color: #3893c5;
        }

        .copyright {
            margin-top: 28px;
            color: #a0acba;
            text-align: center;
            font-size: 11px;
        }

        @media (max-width: 760px) {
            body {
                padding: 16px;
            }

            .login-wrapper {
                max-width: 470px;
                grid-template-columns: 1fr;
                min-height: auto;
                border-radius: 20px;
            }

            .welcome-panel {
                padding: 28px 28px 32px;
                min-height: 245px;
            }

            .brand {
                margin-bottom: 25px;
            }

            .brand-icon {
                width: 42px;
                height: 42px;
            }

            .eyebrow {
                margin-bottom: 12px;
            }

            .welcome-content h1 {
                font-size: 27px;
                margin-bottom: 10px;
            }

            .welcome-content p {
                font-size: 13px;
            }

            .welcome-footer {
                margin-top: 24px;
            }

            .login-panel {
                padding: 35px 28px;
            }

            .login-heading h2 {
                font-size: 26px;
            }
        }

        @media (max-width: 380px) {
            .welcome-panel,
            .login-panel {
                padding-left: 21px;
                padding-right: 21px;
            }

            .welcome-content h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <section class="welcome-panel">
        <div class="brand">
            <div class="brand-icon">WR</div>
            <div>
                <div class="brand-name">WORKING RECORD</div>
                <div class="brand-subtitle">PT EX · Operator System</div>
            </div>
        </div>

        <div class="welcome-content">
            <span class="eyebrow">WORK MANAGEMENT SYSTEM</span>

            <h1>Selamat Datang<br>di Working Record</h1>

            <p>
                Sistem pencatatan aktivitas operator untuk membantu
                pengelolaan issue target, hasil produksi, dan waktu kerja
                secara lebih teratur.
            </p>
        </div>

        <div class="welcome-footer">
            PT EX — Working Record Operator
        </div>
    </section>

    <section class="login-panel">
        <div class="login-heading">
            <div class="small-title">Login Account</div>
            <h2>Masuk Akun</h2>
            <p>
                Silakan masukkan NIK dan password untuk
                melanjutkan ke dashboard.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <span>!</span>
                <div>
                    <strong>Login gagal</strong><br>
                    NIK atau password salah. Silakan periksa kembali.
                </div>
            </div>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <div class="form-group">
                <label for="nik">NIK</label>

                <div class="input-wrapper">
                    <span class="input-icon">▣</span>
                    <input
                        type="text"
                        id="nik"
                        name="nik"
                        placeholder="Masukkan NIK kamu"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <div class="input-wrapper">
                    <span class="input-icon">♙</span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="password-input"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Tampilkan password"
                        aria-pressed="false"
                        title="Tampilkan password"
                    >
                        <!-- Ikon mata -->
                        <svg id="eyeOpen" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>

                        <!-- Ikon mata dicoret -->
                        <svg id="eyeClosed" viewBox="0 0 24 24"
                             aria-hidden="true" style="display:none">
                            <path d="M3 3l18 18"></path>
                            <path d="M10.6 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-4 4.7"></path>
                            <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.1 0 2.1-.2 3-.5"></path>
                            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="login-button">
                Login Sekarang &nbsp; →
            </button>
        </form>

        <div class="security-note">
            <span>●</span>
            Login hanya untuk pengguna yang terdaftar.
        </div>

        <div class="copyright">
            &copy; <?= date('Y') ?> PT EX. Working Record System.
        </div>
    </section>

</div>

<script>
const passwordInput = document.getElementById('password');
const toggleButton = document.getElementById('togglePassword');
const eyeOpen = document.getElementById('eyeOpen');
const eyeClosed = document.getElementById('eyeClosed');

toggleButton.addEventListener('click', function () {
    const showPassword = passwordInput.type === 'password';

    passwordInput.type = showPassword ? 'text' : 'password';

    eyeOpen.style.display = showPassword ? 'none' : 'block';
    eyeClosed.style.display = showPassword ? 'block' : 'none';

    toggleButton.setAttribute('aria-pressed', String(showPassword));
    toggleButton.setAttribute(
        'aria-label',
        showPassword ? 'Sembunyikan password' : 'Tampilkan password'
    );
    toggleButton.setAttribute(
        'title',
        showPassword ? 'Sembunyikan password' : 'Tampilkan password'
    );
});
</script>

</body>
</html>
