
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
            display: flex;
            flex-direction: column;
            position: fixed;
            z-index: 1001;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            padding: 25px 15px;
            overflow-y: auto;
            background: #183b2b;
            color: white;
            transition: transform 0.25s ease;
        }

        .sidebar-brand {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
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

        .sidebar a:hover,
        .sidebar a:focus-visible {
            background: #28513b;
            color: white;
        }

        .sidebar a.active {
            background: #3d7956;
            color: white;
            font-weight: bold;
        }

        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid #41614e;
            padding-top: 18px;
        }

        .admin-info {
            padding: 0 12px 12px;
            color: #dce8df;
            font-size: 13px;
            line-height: 1.6;
        }

        .admin-info small {
            color: #a8c0b0;
        }

        .logout-button {
            width: 100%;
            padding: 12px 15px;
            background: #a83232;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            text-align: left;
            cursor: pointer;
        }

        .logout-button:hover,
        .logout-button:focus-visible {
            background: #c03939;
        }

        .main-content {
            width: calc(100% - 250px);
            min-width: 0;
            min-height: 100vh;
            margin-left: 250px;
            padding: 30px;
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

        .content-card,
        .dashboard-section,
        .stock-chart {
            margin-bottom: 24px;
            padding: 22px;
            border: 1px solid #e5e9e6;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .content-card > :last-child,
        .dashboard-section > :last-child,
        .stock-chart > :last-child {
            margin-bottom: 0;
        }

        .main-content .btn,
        .main-content form button:not([class]),
        .main-content form input[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 9px 14px;
            border: 1px solid transparent;
            border-radius: 6px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .main-content .btn-primary,
        .main-content form button:not([class]),
        .main-content form input[type="submit"] {
            background: #183b2b;
            color: #fff;
        }

        .main-content .btn-primary:hover,
        .main-content form button:not([class]):hover,
        .main-content form input[type="submit"]:hover {
            background: #28513b;
        }

        .main-content .btn-secondary {
            border-color: #d7dfd9;
            background: #f3f6f4;
            color: #34483b;
        }

        .main-content .btn-secondary:hover {
            background: #e7ede9;
        }

        .main-content .btn-danger {
            background: #a83232;
            color: #fff;
        }

        .main-content .btn-danger:hover {
            background: #892828;
        }

        .main-content .btn:focus-visible,
        .main-content form button:focus-visible,
        .main-content form input[type="submit"]:focus-visible {
            outline: 3px solid #8bb99b;
            outline-offset: 2px;
        }

        .main-content .alert {
            margin-bottom: 18px;
            padding: 12px 15px;
            border: 1px solid transparent;
            border-radius: 7px;
        }

        .main-content .alert-success {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .main-content .alert-danger,
        .main-content .alert-error {
            border-color: #fecaca;
            background: #fef2f2;
            color: #b91c1c;
        }

        .main-content .alert-warning {
            border-color: #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .main-content .alert-info {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1e40af;
        }

        .main-content .form-group {
            margin-bottom: 16px;
        }

        .main-content .form-group input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .main-content .form-group select,
        .main-content .form-group textarea {
            width: 100%;
        }

        .main-content .form-group label,
        .main-content label {
            color: #374151;
            font-weight: 600;
        }

        .main-content input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .main-content select,
        .main-content textarea {
            max-width: 100%;
            min-height: 40px;
            padding: 9px 11px;
            border: 1px solid #cbd5ce;
            border-radius: 6px;
            background: #fff;
            color: #26352c;
            font: inherit;
            font-size: 14px;
        }

        .main-content textarea {
            min-height: 96px;
            resize: vertical;
        }

        .main-content input:focus,
        .main-content select:focus,
        .main-content textarea:focus {
            outline: 2px solid #8bb99b;
            outline-offset: 1px;
            border-color: #3d7956;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .main-content table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .main-content table th,
        .main-content table td {
            padding: 11px 12px;
            border-bottom: 1px solid #e5e9e6;
            text-align: left;
            vertical-align: middle;
        }

        .main-content table th {
            background: #f3f6f4;
            color: #34483b;
            font-weight: 600;
            white-space: nowrap;
        }

        .main-content table tbody tr:hover {
            background: #f8faf8;
        }

        .main-content table td[colspan] {
            padding: 24px;
            color: #718078;
            text-align: center;
        }

        .main-content .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.4;
            white-space: nowrap;
        }

        .main-content .badge-success {
            border-color: #bbf7d0;
            background: #dcfce7;
            color: #166534;
        }

        .main-content .badge-danger {
            border-color: #fecaca;
            background: #fee2e2;
            color: #991b1b;
        }

        .main-content .badge-warning {
            border-color: #fde68a;
            background: #fef3c7;
            color: #92400e;
        }

        .main-content .badge-info {
            border-color: #bfdbfe;
            background: #dbeafe;
            color: #1e40af;
        }

        .main-content .badge-secondary {
            border-color: #e5e7eb;
            background: #f3f4f6;
            color: #4b5563;
        }

        .main-content nav[role="navigation"] {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 18px;
            color: #526258;
            font-size: 13px;
        }

        .main-content nav[role="navigation"] > div:last-child,
        .main-content nav[role="navigation"] > div:last-child > div:last-child {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .main-content nav[role="navigation"] a,
        .main-content nav[role="navigation"] [aria-current="page"] > span,
        .main-content nav[role="navigation"] [aria-disabled="true"] > span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            min-height: 38px;
            padding: 7px 10px;
            border: 1px solid #d7dfd9;
            background: #fff;
            color: #34483b;
            text-decoration: none;
        }

        .main-content nav[role="navigation"] a:hover {
            background: #f3f6f4;
        }

        .main-content nav[role="navigation"] [aria-current="page"] > span {
            border-color: #183b2b;
            background: #183b2b;
            color: #fff;
        }

        .main-content nav[role="navigation"] [aria-disabled="true"] > span {
            color: #9ca3af;
        }

        .main-content nav[role="navigation"] > div:first-child {
            display: none;
        }

        .sidebar-overlay,
        .mobile-menu-bar,
        .sidebar-close {
            display: none;
        }

        @media (max-width: 1023px) {
            .sidebar {
                width: min(300px, 85vw);
                transform: translateX(-100%);
            }

            .sidebar-open .sidebar {
                transform: translateX(0);
            }

            .sidebar-overlay {
                position: fixed;
                z-index: 1000;
                inset: 0;
                display: block;
                border: 0;
                padding: 0;
                background: rgb(0 0 0 / 48%);
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.25s ease;
            }

            .sidebar-open .sidebar-overlay {
                opacity: 1;
                pointer-events: auto;
            }

            .sidebar-close {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border: 1px solid #41614e;
                border-radius: 6px;
                background: transparent;
                color: white;
                font-size: 26px;
                line-height: 1;
                cursor: pointer;
            }

            .main-content {
                width: 100%;
                margin-left: 0;
                padding: 20px;
            }

            .mobile-menu-bar {
                position: sticky;
                z-index: 999;
                top: 0;
                display: flex;
                align-items: center;
                gap: 12px;
                min-height: 56px;
                margin: -20px -20px 20px;
                padding: 8px 20px;
                border-bottom: 1px solid #e1e7e3;
                background: #f4f6f5;
            }

            .mobile-menu-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border: 1px solid #cbd5ce;
                border-radius: 7px;
                background: white;
                color: #183b2b;
                cursor: pointer;
            }

            .mobile-menu-toggle:focus-visible,
            .sidebar-close:focus-visible {
                outline: 3px solid #8bb99b;
                outline-offset: 2px;
            }

            .hamburger-icon,
            .hamburger-icon::before,
            .hamburger-icon::after {
                display: block;
                width: 19px;
                height: 2px;
                border-radius: 2px;
                background: currentColor;
            }

            .hamburger-icon {
                position: relative;
            }

            .hamburger-icon::before,
            .hamburger-icon::after {
                position: absolute;
                left: 0;
                content: "";
            }

            .hamburger-icon::before {
                top: -6px;
            }

            .hamburger-icon::after {
                top: 6px;
            }

            .mobile-menu-brand {
                color: #183b2b;
                font-size: 15px;
                font-weight: 700;
            }

            .sidebar-open {
                overflow: hidden;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 16px;
            }

            .content-card,
            .dashboard-section,
            .stock-chart {
                padding: 16px;
            }

            .mobile-menu-bar {
                margin: -16px -16px 18px;
                padding: 7px 16px;
            }

            .main-content nav[role="navigation"] > div:first-child {
                display: flex;
                gap: 8px;
            }

            .main-content nav[role="navigation"] > div:last-child {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .sidebar,
            .sidebar-overlay {
                transition: none;
            }
        }
    </style>
    @stack('styles')
</head>

<body>

    @include('layouts.navbar')

    <main class="main-content">
        <div class="mobile-menu-bar">
            <button
                type="button"
                class="mobile-menu-toggle"
                aria-controls="app-sidebar"
                aria-expanded="false"
                aria-label="Buka navigasi"
            >
                <span class="hamburger-icon" aria-hidden="true"></span>
            </button>
            <span class="mobile-menu-brand">Inventory Seblak Asgar</span>
        </div>

        @yield('content')

    </main>

    <script>
        const sidebar = document.getElementById('app-sidebar');

        if (sidebar) {
            const toggle = document.querySelector('.mobile-menu-toggle');
            const overlay = document.querySelector('.sidebar-overlay');
            const closeButton = document.querySelector('.sidebar-close');
            const mobileLayout = window.matchMedia('(max-width: 1023px)');

            const setSidebarOpen = (isOpen) => {
                document.body.classList.toggle('sidebar-open', isOpen);
                sidebar.inert = mobileLayout.matches && !isOpen;
                sidebar.setAttribute('aria-hidden', String(mobileLayout.matches && !isOpen));
                overlay?.setAttribute('aria-hidden', String(!isOpen));
                toggle?.setAttribute('aria-expanded', String(isOpen));
                toggle?.setAttribute('aria-label', isOpen ? 'Tutup navigasi' : 'Buka navigasi');

                if (isOpen) {
                    closeButton?.focus();
                } else if (mobileLayout.matches) {
                    toggle?.focus();
                }
            };

            setSidebarOpen(false);

            toggle?.addEventListener('click', () => {
                setSidebarOpen(toggle.getAttribute('aria-expanded') !== 'true');
            });

            overlay?.addEventListener('click', () => setSidebarOpen(false));
            closeButton?.addEventListener('click', () => setSidebarOpen(false));

            sidebar.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => setSidebarOpen(false));
            });

            mobileLayout.addEventListener('change', () => setSidebarOpen(false));

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
                    setSidebarOpen(false);
                }
            });
        }
    </script>
    @stack('scripts')

</body>
</html>
