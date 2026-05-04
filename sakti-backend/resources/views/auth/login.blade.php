{{--
    File   : resources/views/auth/login.blade.php
    Fungsi : Halaman login SAKTI — redirect otomatis berdasarkan role
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SAKTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --accent: #e94560; --dark: #1a1a2e; }
        body {
            min-height: 100vh;
            background: url('/images/unhan.jpg') center center / cover no-repeat fixed;
            display: flex; align-items: center; justify-content: center;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background: rgba(10, 10, 30, 0.55);
            z-index: 0;
        }
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            width: 100%; max-width: 420px;
            border: none; border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
            overflow: hidden;
        }
        .login-header {
            background: var(--dark); padding: 2rem;
            text-align: center; border-bottom: 3px solid var(--accent);
        }
        .login-logo {
            width: 64px; height: 64px; border-radius: 50%;
            background: var(--accent);
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: .75rem;
        }
        .login-header h4 {
            color: #fff; font-weight: 700; letter-spacing: 3px; margin: 0;
        }
        .login-header p { color: #a8b0c8; font-size: .8rem; margin: .25rem 0 0; }
        .login-body { background: #fff; padding: 2rem; }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 .2rem rgba(233,69,96,.2);
        }
        .btn-login {
            background: var(--accent); border: 2px solid var(--accent); color: #fff;
            font-weight: 600; letter-spacing: .5px; padding: .65rem;
            border-radius: 8px; transition: background .15s, opacity .15s;
        }
        .btn-login:hover  { background: #c73652; border-color: #c73652; color: #fff; opacity: 1; }
        .btn-login:active { background: #a82a42; border-color: #a82a42; color: #fff; opacity: 1; }
        .btn-login:focus  { background: var(--accent); border-color: var(--accent); color: #fff;
                            box-shadow: 0 0 0 .2rem rgba(233,69,96,.35); outline: none; }
        .login-footer {
            background: #f8f9fa; padding: .75rem 2rem;
            text-align: center; font-size: .75rem; color: #6c757d;
            border-top: 1px solid #eee;
        }

        @media (max-width: 480px) {
            .login-card { border-radius: 0; }
            .login-body, .login-header { padding: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
<div class="login-card card">
    {{-- Header --}}
    <div class="login-header">
        <div class="login-logo">
            <i class="bi bi-shield-lock-fill text-white fs-3"></i>
        </div>
        <h4>SAKTI</h4>
        <p>Sistem Akses Keamanan Terintegrasi<br>KSATRIAN UNHAN RI</p>
    </div>

    {{-- Form --}}
    <div class="login-body">
        @if($errors->any())
            <div class="alert alert-danger py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-success py-2 mb-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold" style="font-size:.85rem;">
                    <i class="bi bi-envelope me-1"></i>Email
                </label>
                <input id="email" type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       name="email" value="{{ old('email') }}"
                       required autofocus autocomplete="off">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label fw-semibold" style="font-size:.85rem;">
                    <i class="bi bi-lock me-1"></i>Password
                </label>
                <div class="input-group">
                    <input id="password" type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           name="password"
                           required autocomplete="new-password">
                    <button class="btn btn-outline-secondary" type="button" id="togglePwd"
                            title="Tampilkan/sembunyikan password">
                        <i class="bi bi-eye" id="togglePwdIcon"></i>
                    </button>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                       {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember" style="font-size:.85rem;">
                    Ingat saya di perangkat ini
                </label>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
        </form>
    </div>

    <div class="login-footer">
        SAKTI &copy; {{ date('Y') }} &mdash; KSATRIAN UNHAN RI
    </div>
</div>
</div><!-- /.login-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const togglePwd  = document.getElementById('togglePwd');
    const pwdInput   = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePwdIcon');
    if (togglePwd) {
        togglePwd.addEventListener('click', () => {
            const isText = pwdInput.type === 'text';
            pwdInput.type = isText ? 'password' : 'text';
            toggleIcon.className = isText ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }
</script>
</body>
</html>
