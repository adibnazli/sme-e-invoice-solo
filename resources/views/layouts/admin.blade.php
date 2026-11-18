<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SME Hardware Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-ice: #E9F1FA;
            --bg-panel: #F5F8FC;
            --surface-white: #FFFFFF;
            --royal-blue: #1D4ED8;
            --royal-navy: #1E3A8A;
            --sky-accent: #38BDF8;
            --sky-soft: #E0F2FE;
            --text-main: #0F172A;
            --text-muted: #94A3B8;
            --text-secondary: #64748B;
            --border-soft: #EEF2F6;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-ice);
            color: var(--text-main);
            min-height: 100vh;
            padding: 1.25rem;
            overflow-x: hidden;
        }

        /* Outer App Shell */
        .app-shell {
            background-color: var(--bg-panel);
            border-radius: 28px;
            min-height: calc(100vh - 2.5rem);
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.06);
            display: flex;
            overflow: hidden;
            border: 4px solid rgba(255, 255, 255, 0.65);
        }

        /* Slim Icon-Only Left Sidebar Rail */
        .icon-rail {
            width: 84px;
            background-color: var(--surface-white);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.75rem 0;
            flex-shrink: 0;
            border-right: 1px solid var(--border-soft);
        }

        .rail-brand {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background-color: #F8FAFC;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            text-decoration: none;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        .rail-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
            width: 100%;
            align-items: center;
        }

        .rail-link {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            text-decoration: none;
            position: relative;
            transition: all 0.2s ease;
        }

        .rail-link:hover {
            color: var(--royal-blue);
            background-color: #F8FAFC;
        }

        .rail-link.active {
            color: var(--text-main);
            background-color: #F8FAFC;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        }

        .rail-link.active::after {
            content: '';
            position: absolute;
            right: -12px;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background-color: var(--sky-accent);
        }

        .rail-action-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px dashed var(--sky-accent);
            color: var(--royal-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            margin-bottom: 1.25rem;
            transition: all 0.2s ease;
        }

        .rail-action-circle:hover {
            background-color: var(--sky-soft);
            color: var(--royal-navy);
        }

        /* Soft Floating Cards & Global Overrides */
        .card {
            background-color: var(--surface-white);
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.025);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid var(--border-soft);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Soft Inputs */
        .form-control, .form-select {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 0.65rem 1rem;
            font-size: 0.88rem;
            color: var(--text-main);
            box-shadow: none !important;
        }

        .form-control:focus, .form-select:focus {
            background-color: #FFFFFF;
            border-color: var(--sky-accent);
        }

        /* Tables (for other modules) */
        .table thead th {
            background-color: #F8FAFC;
            color: var(--text-muted);
            font-size: 0.74rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: none;
            padding: 0.85rem 1.15rem;
        }

        .table tbody td {
            padding: 0.95rem 1.15rem;
            border-bottom: 1px solid var(--border-soft);
            vertical-align: middle;
            font-size: 0.88rem;
        }

        /* Buttons */
        .btn {
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.86rem;
            padding: 0.55rem 1.15rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-sky {
            background-color: #60A5FA;
            color: #FFFFFF;
            border: none;
        }

        .btn-sky:hover {
            background-color: #3B82F6;
            color: #FFFFFF;
        }

        @media (max-width: 767.98px) {
            body { padding: 0; }
            .app-shell { border-radius: 0; border: none; min-height: 100vh; }
        }
    </style>
</head>
<body>

<div class="app-shell">
    <!-- Left Icon-Only Sidebar Rail -->
    <aside class="icon-rail d-none d-md-flex justify-content-between d-print-none">
        <div class="d-flex flex-column align-items-center w-100">
            <a href="{{ Auth::user() && Auth::user()->role === 'OWNER' ? route('dashboard') : route('pos') }}" class="rail-brand" title="SME Hardware Hub">
                <i class="bi bi-grid-fill"></i>
            </a>

            <ul class="rail-nav">
                @if(Auth::user() && Auth::user()->role === 'OWNER')
                <li>
                    <a href="{{ route('dashboard') }}" class="rail-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Owner Dashboard">
                        <i class="bi bi-pie-chart"></i>
                    </a>
                </li>
                @endif

                <li>
                    <a href="{{ route('pos') }}" class="rail-link {{ request()->routeIs('pos') ? 'active' : '' }}" title="POS Terminal">
                        <i class="bi bi-bag-check"></i>
                    </a>
                </li>

                <li>
                    <a href="{{ route('orders.index') }}" class="rail-link {{ request()->routeIs('orders.*') || request()->routeIs('invoice.*') ? 'active' : '' }}" title="Orders & Lorry DO">
                        <i class="bi bi-receipt"></i>
                    </a>
                </li>

                <li>
                    <a href="{{ route('customers.index') }}" class="rail-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Contractors (Hutang)">
                        <i class="bi bi-person-vcard"></i>
                    </a>
                </li>

                @if(Auth::user() && Auth::user()->role === 'OWNER')
                <li>
                    <a href="{{ route('products.index') }}" class="rail-link {{ request()->routeIs('products.*') ? 'active' : '' }}" title="Inventory & GRN">
                        <i class="bi bi-box-seam"></i>
                    </a>
                </li>
                <li>
                    <a href="{{ route('staff.index') }}" class="rail-link {{ request()->routeIs('staff.*') ? 'active' : '' }}" title="Staff & Cashiers">
                        <i class="bi bi-person-badge"></i>
                    </a>
                </li>
                @endif
            </ul>
        </div>

        <div class="d-flex flex-column align-items-center">
            <a href="{{ route('pos') }}" class="rail-action-circle" title="New POS Sale">
                <i class="bi bi-plus-lg"></i>
            </a>
            <button type="button" class="rail-link border-0 bg-transparent text-muted" title="Sign Out" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </aside>

    <!-- Main Workspace -->
    <div class="flex-grow-1 d-flex flex-column min-w-0">
        <!-- Mobile Top Bar -->
        <div class="d-flex d-md-none justify-content-between align-items-center bg-white p-3 border-bottom d-print-none">
            <span class="fw-bold"><i class="bi bi-grid-fill text-primary me-2"></i>Hardware Hub</span>
            <button class="btn btn-sm btn-light border" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
                <i class="bi bi-list fs-5"></i>
            </button>
        </div>

        <main class="flex-grow-1 p-3 p-lg-4">
            @yield('content')
        </main>
    </div>
</div>

<!-- Mobile Offcanvas -->
<div class="offcanvas offcanvas-start d-print-none" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title fw-bold">SME Hardware Hub</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-between">
        <ul class="nav flex-column gap-2">
            @if(Auth::user() && Auth::user()->role === 'OWNER')
            <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link text-dark fw-semibold"><i class="bi bi-pie-chart me-2"></i>Dashboard</a></li>
            @endif
            <li class="nav-item"><a href="{{ route('pos') }}" class="nav-link text-dark fw-semibold"><i class="bi bi-bag-check me-2"></i>POS Terminal</a></li>
            <li class="nav-item"><a href="{{ route('orders.index') }}" class="nav-link text-dark fw-semibold"><i class="bi bi-receipt me-2"></i>Orders & Lorry DO</a></li>
            <li class="nav-item"><a href="{{ route('customers.index') }}" class="nav-link text-dark fw-semibold"><i class="bi bi-person-vcard me-2"></i>Contractors</a></li>
            @if(Auth::user() && Auth::user()->role === 'OWNER')
            <li class="nav-item"><a href="{{ route('products.index') }}" class="nav-link text-dark fw-semibold"><i class="bi bi-box-seam me-2"></i>Inventory</a></li>
            <li class="nav-item"><a href="{{ route('staff.index') }}" class="nav-link text-dark fw-semibold"><i class="bi bi-person-badge me-2"></i>Staff & Cashiers</a></li>
            @endif
        </ul>

        <div class="border-top pt-3">
            <button type="button" class="btn btn-light border text-danger w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
            </button>
        </div>
    </div>
</div>

<!-- Double-Confirmation Logout Modal -->
<div class="modal fade d-print-none" id="logoutConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow" style="border-radius: 22px;">
            <div class="modal-body p-4 text-center">
                <div id="logout-icon-badge" class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width: 56px; height: 56px; background-color: #FEF3C7; color: #D97706; font-size: 1.5rem;">
                    <i id="logout-modal-icon" class="bi bi-box-arrow-right"></i>
                </div>

                <h6 class="fw-bold mb-1" id="logout-modal-title">Sign Out of Terminal?</h6>
                <p class="text-muted small mb-4" id="logout-modal-desc">
                    Are you sure you want to sign out of <strong>{{ Auth::user()->name ?? 'your account' }}</strong>?
                </p>

                <!-- Step 2 Warning Box (Hidden until Step 1 is clicked) -->
                <div id="logout-step2-alert" class="p-2 px-3 mb-3 rounded-3 text-danger small fw-semibold d-none" style="background-color: #FEE2E2;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Final Check: Any unsaved POS cart items will be cleared. Confirm sign out?
                </div>

                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                    @csrf
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light border flex-grow-1" data-bs-dismiss="modal">Cancel</button>

                        <!-- Step 1 Button -->
                        <button type="button" id="btn-logout-step1" class="btn btn-primary flex-grow-1 fw-bold" onclick="triggerSecondLogoutConfirm()">
                            Proceed to Sign Out
                        </button>

                        <!-- Step 2 Final Submit Button -->
                        <button type="submit" id="btn-logout-step2" class="btn btn-danger flex-grow-1 fw-bold d-none">
                            Yes, Sign Out Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function triggerSecondLogoutConfirm() {
        document.getElementById('logout-modal-title').innerText = 'Final Confirmation';
        document.getElementById('logout-icon-badge').style.backgroundColor = '#FEE2E2';
        document.getElementById('logout-icon-badge').style.color = '#DC2626';
        document.getElementById('logout-modal-icon').className = 'bi bi-shield-exclamation';
        document.getElementById('logout-step2-alert').classList.remove('d-none');
        document.getElementById('btn-logout-step1').classList.add('d-none');
        document.getElementById('btn-logout-step2').classList.remove('d-none');
    }

    // Reset modal back to Step 1 whenever it is closed or cancelled
    document.getElementById('logoutConfirmModal')?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('logout-modal-title').innerText = 'Sign Out of Terminal?';
        document.getElementById('logout-icon-badge').style.backgroundColor = '#FEF3C7';
        document.getElementById('logout-icon-badge').style.color = '#D97706';
        document.getElementById('logout-modal-icon').className = 'bi bi-box-arrow-right';
        document.getElementById('logout-step2-alert').classList.add('d-none');
        document.getElementById('btn-logout-step1').classList.remove('d-none');
        document.getElementById('btn-logout-step2').classList.add('d-none');
    });
</script>
</body>
</html>