<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SME E-Invoice System</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; width: 260px; }
        .nav-link { border-radius: 5px; margin-bottom: 5px; transition: 0.2s; }
        .nav-link:hover { background-color: rgba(255,255,255,0.1); }
    </style>
</head>
<body class="bg-light d-flex">

    <!-- Left Sidebar -->
    <div class="sidebar bg-dark text-white p-3 d-flex flex-column">
        <h5 class="fw-bold mb-4 px-2 text-primary">
            <i class="bi bi-tools me-2"></i> SME Hardware
        </h5>
        
        <ul class="nav flex-column mb-auto">
            <!-- Owner Only Links -->
            @if(auth()->user()->role === 'OWNER')
            <li class="nav-item">
                <a href="/dashboard" class="nav-link text-white"><i class="bi bi-bar-chart-fill me-2"></i> Financial Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="/products" class="nav-link text-white"><i class="bi bi-box-seam me-2"></i> Hardware Inventory</a>
            </li>
            @endif
            
            <!-- Accessible to both Owner & Cashier -->
            <li class="nav-item">
                <a href="/customers" class="nav-link text-white"><i class="bi bi-people-fill me-2"></i> Customers & Loyalty</a>
            </li>
            <li class="nav-item">
                <a href="/pos" class="nav-link text-white"><i class="bi bi-cart-check-fill me-2"></i> POS Checkout</a>
            </li>
        </ul>

        <hr class="text-secondary">
        <div class="px-2 mb-2 text-muted small">
            Logged in as: <br>
            <strong class="text-white">{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
        </form>
    </div>

    <!-- Main Content Window -->
    <div class="flex-grow-1 p-4 overflow-auto" style="height: 100vh;">
        @yield('content')
    </div>

</body>
</html>