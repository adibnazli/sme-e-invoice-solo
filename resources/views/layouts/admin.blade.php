<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SME Hardware Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #2c3e50;
        }
        .sidebar {
            background-color: #1a1d20;
            min-height: 100vh;
            color: #fff;
        }
        .sidebar-brand {
            padding: 1.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .nav-link {
            color: #adb5bd;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.05);
            border-left: 4px solid #0d6efd;
        }
        .nav-link i {
            width: 24px;
        }
        .top-header {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            padding: 1rem 2rem;
        }
        .card {
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem 1.5rem;
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }
        .badge {
            font-weight: 500;
            padding: 0.4em 0.8em;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        <div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 sidebar d-none d-md-block d-print-none">
            <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-100">
                <a href="{{ Auth::user() && Auth::user()->role === 'OWNER' ? route('dashboard') : route('pos') }}" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none sidebar-brand w-100">
                    <i class="bi bi-cpu-fill text-primary me-2 fs-4"></i>
                    <span class="fs-5 d-none d-sm-inline">Hardware Hub</span>
                </a>
                <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100 mt-3" id="menu">
                    @if(Auth::user() && Auth::user()->role === 'OWNER')
                    <li class="nav-item w-100 mb-1">
                        <a href="{{ route('dashboard') }}" class="nav-link align-middle px-3 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i> <span class="ms-1 d-none d-sm-inline">Owner Dashboard</span>
                        </a>
                    </li>
                    @endif

                    <li class="nav-item w-100 mb-1">
                        <a href="{{ route('pos') }}" class="nav-link align-middle px-3 {{ request()->routeIs('pos') ? 'active' : '' }}">
                            <i class="bi bi-cart-check-fill"></i> <span class="ms-1 d-none d-sm-inline">POS Terminal</span>
                        </a>
                    </li>

                    <li class="nav-item w-100 mb-1">
                        <a href="{{ route('orders.index') }}" class="nav-link align-middle px-3 {{ request()->routeIs('orders.*') || request()->routeIs('invoice.*') ? 'active' : '' }}">
                            <i class="bi bi-receipt-cutoff"></i> <span class="ms-1 d-none d-sm-inline">Orders & Lorry DO</span>
                        </a>
                    </li>

                    <li class="nav-item w-100 mb-1">
                        <a href="{{ route('customers.index') }}" class="nav-link align-middle px-3 {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i> <span class="ms-1 d-none d-sm-inline">Contractors & Credit</span>
                        </a>
                    </li>

                    @if(Auth::user() && Auth::user()->role === 'OWNER')
                    <li class="nav-item w-100 mb-1">
                        <a href="{{ route('products.index') }}" class="nav-link align-middle px-3 {{ request()->routeIs('products.*') ? 'active' : '' }}">
                            <i class="bi bi-box-seam"></i> <span class="ms-1 d-none d-sm-inline">Inventory & GRN</span>
                        </a>
                    </li>
                    @endif
                </ul>
                <hr class="w-100">
                <div class="dropdown pb-4 w-100 px-3">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="bg-primary rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px;">
                            <strong>{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</strong>
                        </div>
                        <div class="d-none d-sm-inline mx-1 lh-1">
                            <div>{{ Auth::user()->name ?? 'Staff' }}</div>
                            <small class="text-info" style="font-size: 0.7rem;">{{ Auth::user()->role ?? '' }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">Sign out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col d-flex flex-column h-sm-100">
            <div class="top-header d-flex justify-content-between align-items-center d-md-none d-print-none">
                <div class="fw-bold fs-5"><i class="bi bi-cpu-fill text-primary me-2"></i> Hardware Hub</div>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <main class="flex-grow-1 p-4 overflow-auto">
                @yield('content')
            </main>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start bg-dark text-white d-print-none" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title"><i class="bi bi-cpu-fill text-primary me-2"></i> Hardware Hub</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="nav nav-pills flex-column mb-auto">
            @if(Auth::user() && Auth::user()->role === 'OWNER')
            <li class="nav-item mb-1"><a href="{{ route('dashboard') }}" class="nav-link text-white"><i class="bi bi-speedometer2 me-2"></i> Owner Dashboard</a></li>
            @endif
            <li class="nav-item mb-1"><a href="{{ route('pos') }}" class="nav-link text-white"><i class="bi bi-cart-check-fill me-2"></i> POS Terminal</a></li>
            <li class="nav-item mb-1"><a href="{{ route('orders.index') }}" class="nav-link text-white"><i class="bi bi-receipt-cutoff me-2"></i> Orders & Lorry DO</a></li>
            <li class="nav-item mb-1"><a href="{{ route('customers.index') }}" class="nav-link text-white"><i class="bi bi-people-fill me-2"></i> Contractors & Credit</a></li>
            @if(Auth::user() && Auth::user()->role === 'OWNER')
            <li class="nav-item mb-1"><a href="{{ route('products.index') }}" class="nav-link text-white"><i class="bi bi-box-seam me-2"></i> Inventory & GRN</a></li>
            @endif
            <li class="nav-item mt-4 border-top pt-3 border-secondary">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link text-danger w-100 text-start"><i class="bi bi-box-arrow-right me-2"></i> Sign out</button>
                </form>
            </li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>