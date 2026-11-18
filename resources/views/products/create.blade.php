@extends('layouts.admin')

@section('content')
<style>
    /* Royal Blue Gemini Vision Banner */
    .ai-scanner-card {
        background: linear-gradient(135deg, #1E3A8A 0%, #1D4ED8 65%, #2563EB 100%);
        border-radius: 22px;
        padding: 1.5rem 1.75rem;
        color: #FFFFFF;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(29, 78, 216, 0.2);
    }

    .ai-scanner-card::after {
        content: '';
        position: absolute;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.07);
        bottom: -45px;
        right: -30px;
        pointer-events: none;
    }

    .scanner-file-input {
        background-color: rgba(255, 255, 255, 0.95) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 0.55rem 0.9rem !important;
        font-size: 0.85rem !important;
        color: #0F172A !important;
        max-width: 420px;
    }

    /* Form Section Headers */
    .section-step-badge {
        width: 30px;
        height: 30px;
        border-radius: 9px;
        background-color: #E0F2FE;
        color: #0284C7;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.82rem;
    }

    .form-section-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
    }

    .bulk-sub-panel {
        background-color: #F8FAFC;
        border: 1px solid #EEF2F6;
        border-radius: 18px;
        padding: 1.35rem;
    }

    .floor-lock-hint {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.74rem;
        font-weight: 600;
        color: #B45309;
        background-color: #FEF3C7;
        padding: 0.2rem 0.6rem;
        border-radius: 6px;
        margin-top: 0.4rem;
    }
</style>

<!-- Header Row -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Add New Hardware SKU</h4>
        <small class="text-muted">Register item details manually or auto-fill using Gemini Vision</small>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-light border text-secondary fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Inventory
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Gemini Vision SKU Scanner Card -->
<div class="ai-scanner-card mb-4">
    <div class="row align-items-center g-3">
        <div class="col-lg-5">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-white bg-opacity-25 text-white">GEMINI VISION AI</span>
            </div>
            <h5 class="fw-bold mb-1"><i class="bi bi-camera-fill text-info me-2"></i>Smart Hardware SKU Scanner</h5>
            <small class="text-white text-opacity-75">Upload a photo of a tool, box, or cement bag to auto-fill product fields.</small>
        </div>
        <div class="col-lg-7">
            <form action="{{ route('products.scan') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                @csrf
                <input type="file" name="hardware_image" class="form-control scanner-file-input" accept="image/*" required>
                <button type="submit" class="btn btn-sky fw-bold px-3 py-2 flex-shrink-0 shadow-sm">
                    <i class="bi bi-stars me-1"></i> Scan Item & Auto-Fill
                </button>
            </form>
        </div>
    </div>
</div>

@php $ai = session('ai_data', []); @endphp

<!-- Main SKU Creation Card -->
<div class="card p-4">
    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- Section 1: Core SKU Identification -->
        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
            <span class="section-step-badge">1</span>
            <h6 class="form-section-title">Core SKU Identification</h6>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Product Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $ai['name'] ?? '') }}" placeholder="e.g. YTL Castle Cement 50kg" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Category</label>
                <input type="text" name="category" class="form-control" value="{{ old('category', $ai['category'] ?? 'General Hardware') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">SKU Code</label>
                <input type="text" name="sku" class="form-control" value="{{ old('sku', $ai['sku'] ?? '') }}" placeholder="HW-001" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">LHDN Class Code</label>
                <input type="text" name="lhdn_class_code" class="form-control" value="{{ old('lhdn_class_code', '022') }}" required>
            </div>
        </div>

        <!-- Section 2: Pricing, Cost & Floor-Margin Setup -->
        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
            <span class="section-step-badge">2</span>
            <h6 class="form-section-title">Pricing, Cost & Floor-Margin Setup</h6>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Base Unit Type</label>
                <select name="unit_type" class="form-select" required>
                    @foreach(['PIECE', 'BAG', 'KG', 'METER', 'BOX', 'TIN', 'ROLL', 'SET'] as $unit)
                        <option value="{{ $unit }}" {{ old('unit_type', $ai['unit_type'] ?? '') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Supplier Cost Price (RM)</label>
                <input type="number" step="0.01" name="supplier_cost_price" id="supplier_cost_input" class="form-control" placeholder="0.00" required value="{{ old('supplier_cost_price') }}" oninput="updateFloorPreview()">
                <div class="floor-lock-hint">
                    <i class="bi bi-shield-lock-fill"></i>
                    <span id="floor_lock_preview">POS Floor Lock (+8%): RM 0.00</span>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Retail Selling Price (RM)</label>
                <input type="number" step="0.01" name="retail_selling_price" class="form-control" placeholder="0.00" required value="{{ old('retail_selling_price') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Initial Base Stock</label>
                <input type="number" name="stock_quantity" class="form-control" required value="{{ old('stock_quantity', 0) }}">
            </div>
        </div>

        <!-- Section 3: Pecah Pukal (Bulk / Loose Conversion) -->
        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
            <span class="section-step-badge">3</span>
            <h6 class="form-section-title">Pecah Pukal (Bulk / Loose Conversion — Optional)</h6>
        </div>

        <div class="bulk-sub-panel mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Bulk Pack Unit Name</label>
                    <input type="text" name="bulk_unit_type" class="form-control bg-white" value="{{ old('bulk_unit_type') }}" placeholder="e.g. BOX, ROLL, CARTON, PALLET">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Base Units per Bulk Pack</label>
                    <input type="number" name="bulk_conversion_rate" class="form-control bg-white" value="{{ old('bulk_conversion_rate') }}" placeholder="e.g. 50 (1 Box = 50 Pieces)">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Bulk Pack Selling Price (RM)</label>
                    <input type="number" step="0.01" name="bulk_selling_price" class="form-control bg-white" value="{{ old('bulk_selling_price') }}" placeholder="e.g. 110.00">
                </div>
            </div>
        </div>

        <!-- Section 4: Stock Alerts, Image & Specs -->
        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
            <span class="section-step-badge">4</span>
            <h6 class="form-section-title">Stock Threshold, Product Photo & Notes</h6>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Low Stock Alert Threshold</label>
                <input type="number" name="low_stock_threshold" class="form-control" value="{{ old('low_stock_threshold', 10) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Product Image (Optional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Internal Notes / Specs (Optional)</label>
                <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Grade, thickness, supplier contact...">
            </div>
        </div>

        <!-- Submit Footer -->
        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
            <a href="{{ route('products.index') }}" class="btn btn-light border px-4">Cancel</a>
            <button type="submit" class="btn btn-primary px-5 fw-bold">
                <i class="bi bi-check2-circle me-1"></i> Save Hardware SKU
            </button>
        </div>
    </form>
</div>

<script>
    function updateFloorPreview() {
        const cost = parseFloat(document.getElementById('supplier_cost_input').value);
        const label = document.getElementById('floor_lock_preview');
        if (!isNaN(cost) && cost > 0) {
            const floor = (cost * 1.08).toFixed(2);
            label.innerText = `POS Floor Lock (+8%): RM ${floor}`;
        } else {
            label.innerText = 'POS Floor Lock (+8%): RM 0.00';
        }
    }
    document.addEventListener('DOMContentLoaded', updateFloorPreview);
</script>
@endsection