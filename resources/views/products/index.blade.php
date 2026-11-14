@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="bi bi-box-seam me-2"></i> Hardware Inventory & GRN Stock-In</h2>
    <a href="{{ route('products.create') }}" class="btn btn-primary fw-bold">
        <i class="bi bi-plus-lg me-1"></i> Add New Hardware SKU
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success fw-bold">{{ session('success') }}</div>
@endif
@if(session('margin_warning'))
    <div class="alert alert-warning border-warning fw-bold shadow-sm">
        <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i> {{ session('margin_warning') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger fw-bold">{{ session('error') }}</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">SKU / Item</th>
                        <th>Supplier Cost</th>
                        <th>Floor Lock (+8%)</th>
                        <th>Retail & Bulk Price</th>
                        <th>Stock Level</th>
                        <th class="text-end pe-4">GRN Restock / Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td class="ps-4">
                            <strong>{{ $product->name }}</strong><br>
                            <small class="text-muted">{{ $product->sku }} | Base: {{ $product->unit_type }} | LHDN: {{ $product->lhdn_class_code }}</small>
                        </td>
                        <td class="text-muted">RM {{ number_format($product->supplier_cost_price, 2) }}</td>
                        <td>
                            <span class="badge bg-warning text-dark">Min: RM {{ number_format($product->minimum_safe_price, 2) }}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-success">{{ $product->formatted_retail_price }} / {{ $product->unit_type }}</span>
                            @if($product->bulk_unit_type && $product->bulk_conversion_rate)
                                <br><small class="badge bg-info text-dark">Bulk {{ $product->bulk_unit_type }} ({{ $product->bulk_conversion_rate }} {{ $product->unit_type }}): RM {{ number_format($product->bulk_selling_price, 2) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($product->stock_quantity <= $product->low_stock_threshold)
                                <span class="badge bg-danger rounded-pill px-3 py-2">
                                    <i class="bi bi-exclamation-triangle-fill"></i> LOW: {{ $product->stock_quantity }} {{ $product->unit_type }}
                                </span>
                            @else
                                <span class="badge bg-success rounded-pill px-3 py-2">
                                    {{ $product->stock_quantity }} {{ $product->unit_type }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary fw-bold me-1" type="button" data-bs-toggle="collapse" data-bs-target="#restock-{{ $product->id }}">
                                <i class="bi bi-truck"></i> GRN Stock-In
                            </button>
                            <form action="{{ route('products.archive', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Archive this product?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Archive">
                                    <i class="bi bi-archive-fill"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse bg-light" id="restock-{{ $product->id }}">
                        <td colspan="6" class="p-3 ps-4">
                            <form action="{{ route('products.restock', $product->id) }}" method="POST" class="row g-2 align-items-center">
                                @csrf
                                <div class="col-auto">
                                    <span class="fw-bold text-primary"><i class="bi bi-box-arrow-in-down"></i> Receive Lorry Stock ({{ $product->name }}):</span>
                                </div>
                                <div class="col-md-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">+ Qty ({{ $product->unit_type }})</span>
                                        <input type="number" name="added_quantity" class="form-control" required min="1" placeholder="50">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">New Supplier Cost (RM)</span>
                                        <input type="number" step="0.01" name="new_supplier_cost" class="form-control" required value="{{ $product->supplier_cost_price }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="auto_adjust_retail" value="1" id="autoAdj{{ $product->id }}" checked>
                                        <label class="form-check-label small fw-bold" for="autoAdj{{ $product->id }}">
                                            Auto-bump retail price if margin drops &lt; 15%
                                        </label>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-success fw-bold">Confirm GRN</button>
                                </div>
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