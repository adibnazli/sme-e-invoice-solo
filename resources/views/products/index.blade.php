@extends('layouts.admin')

@section('content')
<style>
    /* Top Search & Filter Bar */
    .inventory-top-bar {
        background: #FFFFFF;
        border-radius: 20px;
        padding: 0.9rem 1.35rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.025);
    }

    .inventory-search-input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 0.88rem;
        color: #0F172A;
    }

    .inventory-filter-pill {
        border: 1px solid #E2E8F0;
        background: #F8FAFC;
        color: #64748B;
        border-radius: 10px;
        padding: 0.38rem 0.85rem;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .inventory-filter-pill.active, .inventory-filter-pill:hover {
        background: #E0F2FE;
        color: #0284C7;
        border-color: #BAE6FD;
    }

    /* Product Thumbnail Box */
    .sku-thumb-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background-color: #F8FAFC;
        border: 1px solid #EEF2F6;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .sku-thumb-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 3px;
    }

    /* Soft FinTech Pills */
    .soft-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.73rem;
        font-weight: 700;
        padding: 0.35em 0.75em;
        border-radius: 8px;
    }

    .pill-floor {
        background-color: #FEF3C7;
        color: #B45309;
    }

    .pill-bulk {
        background-color: #E0F2FE;
        color: #0284C7;
    }

    .pill-stock-ok {
        background-color: #DCFCE7;
        color: #15803D;
    }

    .pill-stock-low {
        background-color: #FEE2E2;
        color: #DC2626;
    }
</style>

<!-- Header Row -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Hardware Inventory & GRN Stock-In</h4>
        <small class="text-muted">Manage supplier costs, +8% floor price locks, bulk conversions, and lorry stock arrivals</small>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary fw-bold">
        <i class="bi bi-plus-lg me-1"></i> Add New Hardware SKU
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
    </div>
@endif
@if(session('margin_warning'))
    <div class="alert alert-warning border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i> {{ session('margin_warning') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
    </div>
@endif

<!-- Search & Quick Filter Bar -->
<div class="inventory-top-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 220px;">
        <i class="bi bi-search text-muted"></i>
        <input type="text" id="inventory_search_input" class="inventory-search-input" placeholder="Search item name, SKU code, or LHDN classification..." oninput="filterInventory()">
    </div>
    <div class="d-flex align-items-center gap-1 flex-wrap">
        <button type="button" class="inventory-filter-pill active" onclick="setInventoryFilter('ALL', this)">All SKUs</button>
        <button type="button" class="inventory-filter-pill" onclick="setInventoryFilter('LOW', this)">Low Stock Alerts</button>
        <button type="button" class="inventory-filter-pill" onclick="setInventoryFilter('BULK', this)">Bulk Enabled</button>

        <select id="inventory_unit_filter" class="form-select form-select-sm py-1 ps-2 pe-4 ms-1" style="width: auto; font-size: 0.78rem; border-radius: 10px;" onchange="filterInventory()">
            <option value="">All Base Units</option>
            @foreach($products->pluck('unit_type')->unique()->filter() as $unit)
                <option value="{{ strtolower($unit) }}">{{ $unit }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Main Inventory Table Card -->
<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">SKU / Item</th>
                    <th>Supplier Cost</th>
                    <th>Floor Lock (+8%)</th>
                    <th>Retail & Bulk Price</th>
                    <th>Stock Level</th>
                    <th class="text-end pe-3">GRN Restock / Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                @php
                    $isLowStock = $product->stock_quantity <= $product->low_stock_threshold;
                    $hasBulk = !empty($product->bulk_unit_type) && $product->bulk_conversion_rate > 1;
                @endphp
                <tr class="inventory-row"
                    data-search="{{ strtolower($product->name . ' ' . $product->sku . ' ' . $product->lhdn_class_code) }}"
                    data-unit="{{ strtolower($product->unit_type) }}"
                    data-low="{{ $isLowStock ? '1' : '0' }}"
                    data-bulk="{{ $hasBulk ? '1' : '0' }}">
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sku-thumb-box">
                                @if($product->image_path)
                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                                @else
                                    <i class="bi bi-box-seam text-secondary opacity-50"></i>
                                @endif
                            </div>
                            <div>
                                <div class="fw-bold">{{ $product->name }}</div>
                                <small class="text-muted" style="font-size: 0.74rem;">
                                    {{ $product->sku }} • Base: {{ $product->unit_type }} • LHDN: {{ $product->lhdn_class_code }}
                                </small>
                            </div>
                        </div>
                    </td>
                    <td class="text-secondary fw-medium">
                        RM {{ number_format($product->supplier_cost_price, 2) }}
                    </td>
                    <td>
                        <span class="soft-pill pill-floor">
                            Min: RM {{ number_format($product->minimum_safe_price, 2) }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-primary">{{ $product->formatted_retail_price }} <small class="text-muted fw-normal">/ {{ $product->unit_type }}</small></div>
                        @if($hasBulk)
                            <span class="soft-pill pill-bulk mt-1">
                                Bulk {{ $product->bulk_unit_type }} ({{ $product->bulk_conversion_rate }} {{ $product->unit_type }}): RM {{ number_format($product->bulk_selling_price, 2) }}
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($isLowStock)
                            <span class="soft-pill pill-stock-low">
                                <i class="bi bi-exclamation-triangle-fill"></i> LOW: {{ $product->stock_quantity }} {{ $product->unit_type }}
                            </span>
                        @else
                            <span class="soft-pill pill-stock-ok">
                                {{ $product->stock_quantity }} {{ $product->unit_type }}
                            </span>
                        @endif
                    </td>
                    <td class="text-end pe-3">
                        <button class="btn btn-sm btn-primary fw-semibold me-1"
                                type="button"
                                data-action="{{ route('products.restock', $product->id) }}"
                                data-name="{{ $product->name }}"
                                data-sku="{{ $product->sku }}"
                                data-unit="{{ $product->unit_type }}"
                                data-stock="{{ $product->stock_quantity }}"
                                data-cost="{{ $product->supplier_cost_price }}"
                                onclick="openGrnModal(this)">
                            <i class="bi bi-truck me-1"></i> GRN Stock-In
                        </button>
                        <form action="{{ route('products.archive', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Archive this product?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Archive">
                                <i class="bi bi-archive"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted small">No hardware products in inventory yet.</td>
                </tr>
                @endforelse

                <tr id="inventory_no_results" class="d-none">
                    <td colspan="6" class="text-center py-5 text-muted small">No matching hardware SKUs found.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: GRN Lorry Stock-In -->
<div class="modal fade" id="grnRestockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <form id="grnRestockForm" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Receive Lorry GRN Stock-In</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 rounded-4 mb-3" style="background-color: #F8FAFC; border: 1px solid #EEF2F6;">
                        <small class="text-muted d-block" id="grnModalSku" style="font-size: 0.72rem;">SKU</small>
                        <div class="fw-bold text-dark mb-2" id="grnModalName">-</div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top small">
                            <span class="text-muted">Current On-Hand Stock</span>
                            <span class="fw-bold text-primary" id="grnModalCurrentStock">0</span>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold" id="grnQtyLabel">+ Received Qty</label>
                            <input type="number" name="added_quantity" class="form-control" required min="1" placeholder="50">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">New Supplier Cost (RM)</label>
                            <input type="number" step="0.01" name="new_supplier_cost" id="grnCostInput" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-check p-3 rounded-3" style="background-color: #F8FAFC; padding-left: 2.25rem !important;">
                        <input class="form-check-input" type="checkbox" name="auto_adjust_retail" value="1" id="grnAutoAdjustCheck" checked>
                        <label class="form-check-label small fw-semibold text-dark" for="grnAutoAdjustCheck">
                            Auto-bump retail price if margin drops &lt; 15%
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Confirm GRN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentInventoryFilter = 'ALL';

    function setInventoryFilter(type, btnEl) {
        currentInventoryFilter = type;
        document.querySelectorAll('.inventory-filter-pill').forEach(b => b.classList.remove('active'));
        btnEl.classList.add('active');
        filterInventory();
    }

    function filterInventory() {
        const query = document.getElementById('inventory_search_input').value.toLowerCase().trim();
        const unitFilter = document.getElementById('inventory_unit_filter').value;
        const rows = document.querySelectorAll('.inventory-row');
        let visible = 0;

        rows.forEach(row => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const matchesUnit = !unitFilter || row.dataset.unit === unitFilter;
            const matchesFilter =
                currentInventoryFilter === 'ALL' ||
                (currentInventoryFilter === 'LOW' && row.dataset.low === '1') ||
                (currentInventoryFilter === 'BULK' && row.dataset.bulk === '1');

            if (matchesSearch && matchesUnit && matchesFilter) {
                row.classList.remove('d-none');
                visible++;
            } else {
                row.classList.add('d-none');
            }
        });

        const noRes = document.getElementById('inventory_no_results');
        if (noRes && rows.length > 0) {
            noRes.classList.toggle('d-none', visible > 0);
        }
    }

    function openGrnModal(btnEl) {
        document.getElementById('grnRestockForm').action = btnEl.dataset.action;
        document.getElementById('grnModalName').innerText = btnEl.dataset.name;
        document.getElementById('grnModalSku').innerText = btnEl.dataset.sku;
        document.getElementById('grnModalCurrentStock').innerText = btnEl.dataset.stock + ' ' + btnEl.dataset.unit;
        document.getElementById('grnQtyLabel').innerText = '+ Qty (' + btnEl.dataset.unit + ')';
        document.getElementById('grnCostInput').value = parseFloat(btnEl.dataset.cost).toFixed(2);

        const modal = new bootstrap.Modal(document.getElementById('grnRestockModal'));
        modal.show();
    }
</script>
@endsection