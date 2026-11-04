@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="bi bi-box-seam me-2"></i> Hardware Inventory</h2>
    <a href="#" class="btn btn-primary fw-bold">
        <i class="bi bi-camera-fill me-1"></i> Add Product via AI Scan
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">SKU / Item</th>
                        <th>Category</th>
                        <th>Retail Price</th>
                        <th>Stock Level</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td class="ps-4">
                            <strong>{{ $product->name }}</strong><br>
                            <small class="text-muted">{{ $product->sku }} | {{ $product->unit_type }}</small>
                        </td>
                        <td><span class="badge bg-secondary">{{ $product->category }}</span></td>
                        <td class="fw-bold text-success">{{ $product->formatted_retail_price }}</td>
                        <td>
                            @if($product->stock_quantity <= $product->low_stock_threshold)
                                <span class="badge bg-danger rounded-pill px-3 py-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i> LOW: {{ $product->stock_quantity }}
                                </span>
                            @else
                                <span class="badge bg-success rounded-pill px-3 py-2">
                                    {{ $product->stock_quantity }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <form action="{{ route('products.archive', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this product?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Archive">
                                    <i class="bi bi-archive-fill"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection