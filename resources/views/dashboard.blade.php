@extends('layouts.admin')

@section('content')
<h2 class="fw-bold mb-4"><i class="bi bi-speedometer2 me-2"></i> Business Overview</h2>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-robot fs-5 text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Groq AI Predictive Insights</h5>
                </div>
                
                <div id="ai-insight-container" style="min-height: 80px;">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span class="text-muted">Analyzing Aiven database, historical sales, and seasonal trends...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h6 class="text-uppercase fw-bold mb-2">Today's Revenue</h6>
                <h2 class="fw-bold mb-0">RM {{ number_format($todayRevenue, 2) }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h6 class="text-uppercase fw-bold mb-2">Total All-Time Revenue</h6>
                <h2 class="fw-bold mb-0">RM {{ number_format($totalRevenue, 2) }}</h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-info text-white shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h6 class="text-uppercase fw-bold mb-2">Today's Orders</h6>
                <h2 class="fw-bold mb-0">{{ $todayOrders }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-danger text-white fw-bold py-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> Low Stock Alerts
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Product Name</th>
                        <th>SKU</th>
                        <th>Threshold</th>
                        <th>Current Stock</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lowStockProducts as $product)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $product->name }}</td>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->low_stock_threshold }}</td>
                        <td>
                            <span class="badge bg-danger rounded-pill px-3 py-2">
                                {{ $product->stock_quantity }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary fw-bold">Manage Stock</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted fw-bold">
                            <i class="bi bi-check-circle-fill text-success me-2"></i> All hardware stock levels are optimal.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch('{{ route('api.ai.insights') }}')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('ai-insight-container');
            if(data.status === 'success') {
                container.innerHTML = data.insights;
            } else {
                container.innerHTML = `<p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i> ${data.message}</p>`;
            }
        })
        .catch(error => {
            document.getElementById('ai-insight-container').innerHTML = '<p class="text-danger">Failed to load AI insights.</p>';
        });
});
</script>
@endsection