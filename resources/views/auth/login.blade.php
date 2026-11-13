<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Inventori Seblak Asgar</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f6f2;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #14532d;
            color: white;
            border-radius: 15px;
            font-size: 28px;
            font-weight: bold;
        }

        .login-header h2 {
            color: #14532d;
            margin-bottom: 8px;
        }

        .login-header p {
            color: #777;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus {
            border-color: #15803d;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            font-size: 14px;
            color: #555;
        }

        .remember input {
            width: 15px;
            height: 15px;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #14532d;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #166534;
        }

        .alert {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 7px;
            font-size: 13px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
        }

        .footer {
            text-align: center;
            margin-top: 25px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="login-header">
        <div class="login-logo">SA</div>

        <h2>Seblak Asgar</h2>
        <p>Silakan login untuk mengakses sistem inventori</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login.process') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="email">Email Admin</label>
            <input
                type="email"
                name="email"
                id="email"
                value="{{ old('email') }}"
                placeholder="Masukkan email admin"
                required
                autofocus
            >
            @error('email')
                <small style="display:block; margin-top:5px; color:#b91c1c;">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                name="password"
                id="password"
                placeholder="Masukkan password"
                required
            >
            @error('password')
                <small style="display:block; margin-top:5px; color:#b91c1c;">{{ $message }}</small>
            @enderror
        </div>

        <label class="remember" for="remember">
            <input id="remember" type="checkbox" name="remember" value="1">
            Ingat saya
        </label>

        <button type="submit" class="btn-login">
            Login
        </button>
    </form>

    <div class="footer">
        &copy; {{ date('Y') }} Seblak Asgar - Sistem Inventori
    </div>

</div>

</body>
</html>