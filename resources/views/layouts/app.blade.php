
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Inventory Seblak Asgar')</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f5;
            color: #26352c;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #183b2b;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 15px;
            overflow-y: auto;
        }

        .sidebar-brand {
            font-size: 19px;
            font-weight: bold;
            padding: 0 12px 25px;
            border-bottom: 1px solid #41614e;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .sidebar-title {
            font-size: 11px;
            color: #a8c0b0;
            margin: 20px 12px 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: block;
            color: #dce8df;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.2s;
        }

        .sidebar a:hover {
            background: #28513b;
            color: white;
        }

        .sidebar a.active {
            background: #3d7956;
            color: white;
            font-weight: bold;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .page-header p {
            color: #718078;
            margin: 0;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 190px;
            }

            .main-content {
                margin-left: 190px;
                padding: 15px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    @include('layouts.navbar')

    <main class="main-content">

        @yield('content')

    </main>

    @stack('scripts')

</body>
</html>
