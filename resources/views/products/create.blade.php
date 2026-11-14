@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="bi bi-plus-square me-2"></i> Add New Hardware SKU</h2>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary fw-bold">Cancel</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <h5 class="fw-bold text-primary mb-3"><i class="bi bi-camera me-2"></i> Gemini Vision SKU Scanner</h5>
        @if ($errors->any())
            <div class="alert alert-danger fw-bold shadow-sm">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form action="{{ route('products.scan') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center">
            @csrf
            <input type="file" name="hardware_image" class="form-control me-3" accept="image/*" required style="max-width: 400px;">
            <button type="submit" class="btn btn-primary fw-bold">Scan Item & Auto-Fill</button>
        </form>
    </div>
</div>

@php $ai = session('ai_data', []); @endphp

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <h5 class="fw-bold border-bottom pb-2 mb-3">1. Core SKU Identification</h5>
            <div class="row mb-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold">Product Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $ai['name'] ?? '') }}" placeholder="e.g. YTL Castle Cement 50kg" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Category</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', $ai['category'] ?? 'General Hardware') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">SKU Code</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $ai['sku'] ?? '') }}" placeholder="HW-001" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">LHDN Class Code</label>
                    <input type="text" name="lhdn_class_code" class="form-control" value="{{ old('lhdn_class_code', '022') }}" required>
                </div>
            </div>

            <h5 class="fw-bold border-bottom pb-2 mb-3 mt-4">2. Pricing, Cost & Floor-Margin Setup</h5>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Base Unit Type</label>
                    <select name="unit_type" class="form-select" required>
                        @foreach(['PIECE', 'BAG', 'KG', 'METER', 'BOX', 'TIN', 'ROLL', 'SET'] as $unit)
                            <option value="{{ $unit }}" {{ old('unit_type', $ai['unit_type'] ?? '') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Supplier Cost Price (RM)</label>
                    <input type="number" step="0.01" name="supplier_cost_price" class="form-control" required value="{{ old('supplier_cost_price') }}">
                    <div class="form-text text-danger">POS floor lock = Cost + 8%</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Retail Selling Price (RM)</label>
                    <input type="number" step="0.01" name="retail_selling_price" class="form-control" required value="{{ old('retail_selling_price') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Initial Base Stock</label>
                    <input type="number" name="stock_quantity" class="form-control" required value="{{ old('stock_quantity', 0) }}">
                </div>
            </div>

            <h5 class="fw-bold border-bottom pb-2 mb-3 mt-4">3. Pecah Pukal (Bulk / Loose Conversion — Optional)</h5>
            <div class="row mb-3 bg-light p-3 rounded">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Bulk Pack Unit Name</label>
                    <input type="text" name="bulk_unit_type" class="form-control" value="{{ old('bulk_unit_type') }}" placeholder="e.g. BOX, ROLL, CARTON, PALLET">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Base Units per Bulk Pack</label>
                    <input type="number" name="bulk_conversion_rate" class="form-control" value="{{ old('bulk_conversion_rate') }}" placeholder="e.g. 50 (1 Box = 50 Pieces)">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Bulk Pack Selling Price (RM)</label>
                    <input type="number" step="0.01" name="bulk_selling_price" class="form-control" value="{{ old('bulk_selling_price') }}" placeholder="e.g. 110.00">
                </div>
            </div>

            <div class="row mb-4 mt-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Low Stock Alert Threshold</label>
                    <input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', 10) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Product Image (Optional)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Internal Notes / Specs (Optional)</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Grade, thickness, supplier contact...">
                </div>
            </div>

            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold">Save Hardware SKU</button>
        </form>
    </div>
</div>
@endsection