@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="bi bi-plus-square me-2"></i> Add New Product</h2>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary fw-bold">Cancel</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<!-- AI Scanner Section -->
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <h5 class="fw-bold text-primary mb-3"><i class="bi bi-magic me-2"></i> Gemini Vision Auto-Fill</h5>
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
            <button type="submit" class="btn btn-primary fw-bold">Scan & Auto-Fill</button>
        </form>
    </div>
</div>

<!-- Product Details Form -->
@php $ai = session('ai_data', []); @endphp

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Product Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $ai['name'] ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Category</label>
                    <input type="text" name="category" class="form-control" value="{{ old('category', $ai['category'] ?? '') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Product Image (Optional)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <div class="form-text">Accepted formats: JPG, PNG, WEBP (Max 2MB)</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">SKU</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $ai['sku'] ?? '') }}" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Unit Type</label>
                    <select name="unit_type" class="form-select" required>
                        @foreach(['PIECE', 'BAG', 'KG', 'METER', 'BOX', 'TIN', 'ROLL', 'SET'] as $unit)
                            <option value="{{ $unit }}" {{ old('unit_type', $ai['unit_type'] ?? '') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-bold mb-0">Description (Optional)</label>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="ai-btn" onclick="generateDesc()">
                            <i class="bi bi-stars"></i> Auto-Fill AI
                        </button>
                    </div>
                    <textarea name="description" id="desc_box" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Supplier Cost Price (RM)</label>
                        <input type="number" step="0.01" name="supplier_cost_price" class="form-control" required value="{{ old('supplier_cost_price') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Retail Selling Price (RM)</label>
                        <input type="number" step="0.01" name="retail_selling_price" class="form-control" required value="{{ old('retail_selling_price') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Initial Stock</label>
                        <input type="number" name="stock_quantity" class="form-control" required value="{{ old('stock_quantity') }}">
                    </div>
                </div>

            <button type="submit" class="btn btn-success btn-lg px-5 fw-bold">Save Product</button>
        </form>
    </div>
</div>

<script>
async function generateDesc() {
    let name = document.querySelector('input[name="name"]').value;
    if (!name) return alert('Please enter a product name first.');
    
    let btn = document.getElementById('ai-btn');
    let originalText = btn.innerHTML;
    btn.innerHTML = 'Generating...';
    btn.disabled = true;
    
    try {
        let res = await fetch(`/generate-description?name=${encodeURIComponent(name)}`);
        
        // If Laravel throws a 500 HTML page anyway, this catches it
        if (!res.ok && res.headers.get('content-type').indexOf('application/json') === -1) {
            throw new Error("Server returned HTML instead of JSON. Check terminal.");
        }

        let data = await res.json();
        
        if (data.status === 'success') {
            document.getElementById('desc_box').value = data.description;
        } else {
            alert(data.message); // This will print the exact PHP/cURL error!
        }
    } catch (e) {
        alert('Critical Crash: ' + e.message);
    }
    
    btn.innerHTML = originalText;
    btn.disabled = false;
}
</script>
@endsection