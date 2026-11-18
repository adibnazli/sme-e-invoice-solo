@extends('layouts.admin')

@section('content')
<style>
    /* Royal Blue AI Parser Bar */
    .ai-parser-card {
        background: linear-gradient(135deg, #1E3A8A 0%, #1D4ED8 65%, #2563EB 100%);
        border-radius: 20px;
        padding: 1.15rem 1.35rem;
        color: #FFFFFF;
        box-shadow: 0 8px 22px rgba(29, 78, 216, 0.18);
    }

    .ai-parser-input {
        background-color: rgba(255, 255, 255, 0.95) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 0.6rem 1rem !important;
        font-size: 0.86rem !important;
        color: #0F172A !important;
    }

    /* Modal Warning Pills */
    .ai-warn-pill {
        display: inline-block;
        font-size: 0.68rem;
        font-weight: 700;
        padding: 0.15rem 0.5rem;
        border-radius: 6px;
        margin-right: 0.35rem;
        text-transform: uppercase;
    }

    .pill-capped {
        background-color: #FEF3C7;
        color: #D97706;
        border: 1px solid #FCD34D;
    }

    .pill-unmatched {
        background-color: #FEE2E2;
        color: #DC2626;
        border: 1px solid #FCA5A5;
    }

    .pos-modal-list-box {
        background-color: #F8FAFC;
        border: 1px solid #EEF2F6;
        border-radius: 14px;
        padding: 0.85rem 1rem;
        text-align: left;
        max-height: 220px;
        overflow-y: auto;
    }

    .pos-modal-item {
        font-size: 0.82rem;
        color: #334155;
        padding: 0.4rem 0;
        border-bottom: 1px dashed #E2E8F0;
        line-height: 1.45;
    }

    .pos-modal-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .pos-modal-item:first-child {
        padding-top: 0;
    }

    /* Clean Floating Product Cards */
    .pos-product-card {
        background: #FFFFFF;
        border: none;
        border-radius: 18px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.025);
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        overflow: hidden;
    }

    .pos-product-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .pos-product-img {
        height: 160px;
        background-color: #F8FAFC;
        position: relative;
        overflow: hidden;
        padding: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pos-product-img img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: 8px;
    }

    .catalog-filter-bar {
        background: #FFFFFF;
        border-radius: 16px;
        padding: 0.65rem 1rem;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.025);
    }

    .filter-pill {
        border: 1px solid #E2E8F0;
        background: #F8FAFC;
        color: #64748B;
        border-radius: 10px;
        padding: 0.35rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .filter-pill.active, .filter-pill:hover {
        background: #E0F2FE;
        color: #0284C7;
        border-color: #BAE6FD;
    }

    .stock-pill {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.3em 0.65em;
        border-radius: 8px;
    }

    .stock-ok {
        background-color: #E0F2FE;
        color: #0284C7;
    }

    .stock-low {
        background-color: #FEE2E2;
        color: #DC2626;
    }

    .bulk-pill {
        background-color: #F0F9FF;
        color: #0369A1;
        border: 1px solid #BAE6FD;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        display: block;
    }

    /* Right-Hand White Checkout Panel */
    .pos-checkout-panel {
        background: #FFFFFF;
        border-radius: 24px;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.03);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 4.5rem);
        overflow: hidden;
    }

    /* Cart Item Row */
    .cart-row {
        padding: 0.85rem 0;
        border-bottom: 1px solid #F1F5F9;
    }

    .cart-row:last-child {
        border-bottom: none;
    }

    .cart-mini-input {
        background-color: #F8FAFC !important;
        border: 1px solid #E2E8F0 !important;
        border-radius: 8px !important;
        padding: 0.3rem 0.5rem !important;
        font-size: 0.82rem !important;
        font-weight: 600;
    }

    .unit-switch-btn {
        background-color: #E0F2FE;
        color: #0284C7;
        border: none;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
    }

    .unit-switch-btn:hover {
        background-color: #BAE6FD;
        color: #0369A1;
    }

    .checkout-footer {
        background-color: #FAFBFD;
        border-top: 1px solid #EEF2F6;
        padding: 1.15rem 1.35rem;
    }

    .form-label-pos {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748B;
        margin-bottom: 0.25rem;
    }
</style>

<div class="row g-4">
    <!-- LEFT COLUMN: CATALOG & AI PARSER (7 COLS) -->
    <div class="col-lg-7 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold mb-0">SME Hardware POS</h4>
                <small class="text-muted">Click an item or paste a contractor message below</small>
            </div>
            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: #E0F2FE; color: #0284C7;">
                <i class="bi bi-shield-lock-fill me-1"></i> Floor Lock: Cost + 8% Active
            </span>
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

        <!-- AI Contractor WhatsApp / Telegram Order Parser -->
        <div class="ai-parser-card mb-3">
            <label class="form-label fw-bold small text-white mb-2 d-flex align-items-center gap-2">
                <i class="bi bi-whatsapp text-info"></i>
                <span>AI Contractor WhatsApp / Telegram Order Parser</span>
            </label>
            <div class="d-flex gap-2">
                <input type="text" id="ai_order_input" class="form-control ai-parser-input" placeholder="e.g. Boss hantar 10 beg simen YTL, 2 kotak paku pergi site...">
                <button class="btn btn-sky fw-bold flex-shrink-0 px-3" type="button" id="btn_parse_ai" onclick="parseContractorOrder()">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Parse to Cart
                </button>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div class="catalog-filter-bar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 200px;">
                <i class="bi bi-search text-muted"></i>
                <input type="text" id="pos_search_input" class="border-0 bg-transparent w-100 small" style="outline: none;" placeholder="Search item name or SKU..." oninput="filterPosCatalog()">
            </div>

            <div class="d-flex align-items-center gap-1 flex-wrap">
                <button type="button" class="filter-pill active" onclick="setPosFilter('ALL', this)">All</button>
                <button type="button" class="filter-pill" onclick="setPosFilter('BULK', this)">Bulk Available</button>
                <button type="button" class="filter-pill" onclick="setPosFilter('LOW', this)">Low Stock</button>

                <select id="pos_unit_filter" class="form-select form-select-sm py-1 ps-2 pe-4 ms-1" style="width: auto; font-size: 0.78rem; border-radius: 10px;" onchange="filterPosCatalog()">
                    <option value="">All Units</option>
                    @foreach($products->pluck('unit_type')->unique()->filter() as $unit)
                        <option value="{{ strtolower($unit) }}">{{ $unit }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="row g-3 overflow-auto pe-1" id="pos_product_grid" style="max-height: calc(100vh - 19rem);">
            @foreach($products as $product)
            @php
                $isLowStock = $product->stock_quantity <= $product->low_stock_threshold;
                $hasBulk = !empty($product->bulk_unit_type) && $product->bulk_conversion_rate > 1;
            @endphp
            <div class="col-md-4 col-sm-6 pos-product-item"
                 data-name="{{ strtolower($product->name) }}"
                 data-sku="{{ strtolower($product->sku) }}"
                 data-unit="{{ strtolower($product->unit_type) }}"
                 data-bulk="{{ $hasBulk ? '1' : '0' }}"
                 data-low="{{ $isLowStock ? '1' : '0' }}">
                <div class="pos-product-card h-100 d-flex flex-column" onclick="addToCart({{ $product->id }})">
                    <div class="pos-product-img">
                        @if($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}">
                        @else
                            <i class="bi bi-box-seam" style="font-size: 2.5rem; color: #CBD5E1;"></i>
                        @endif
                        <span class="stock-pill {{ !$isLowStock ? 'stock-ok' : 'stock-low' }}">
                            {{ $product->stock_quantity }} {{ $product->unit_type }}
                        </span>
                    </div>

                    <div class="p-3 d-flex flex-column flex-grow-1 justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1 text-truncate" style="font-size: 0.9rem;" title="{{ $product->name }}">{{ $product->name }}</h6>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted" style="font-size: 0.74rem;">{{ $product->sku }}</small>
                                <span class="fw-bold text-primary" style="font-size: 0.86rem;">RM {{ number_format($product->retail_selling_price, 2) }}<small class="text-muted fw-normal">/{{ $product->unit_type }}</small></span>
                            </div>
                        </div>

                        @if($hasBulk)
                            <div class="mt-2">
                                <span class="bulk-pill text-truncate">
                                    {{ $product->bulk_unit_type }} ({{ $product->bulk_conversion_rate }}): RM {{ number_format($product->bulk_selling_price, 2) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            <div id="pos_no_results" class="col-12 text-center text-muted py-5 d-none small">
                <i class="bi bi-search fs-3 d-block mb-2 opacity-50"></i>
                No matching hardware items found.
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: CURRENT ORDER & CHECKOUT (5 COLS) -->
    <div class="col-lg-5">
        <div class="pos-checkout-panel">
            <!-- Cart Header -->
            <div class="p-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0">Current Order & Bargaining</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Adjust quantities or negotiated unit prices</small>
                </div>
                <button class="btn btn-sm btn-light border text-secondary py-1 px-3" onclick="clearCart()">Clear</button>
            </div>

            <!-- Cart Items List -->
            <div class="flex-grow-1 p-3 px-4 overflow-auto" id="cart-items">
                <div class="text-center text-muted mt-5 small">
                    <i class="bi bi-cart3 fs-2 d-block mb-2 text-secondary opacity-50"></i>
                    Cart is empty. Click a product or paste a contractor message above.
                </div>
            </div>

            <!-- Checkout Form Footer -->
            <div class="checkout-footer">
                <form action="{{ route('pos.checkout') }}" method="POST" id="checkout-form" onsubmit="return validateCheckout()">
                    @csrf
                    <input type="hidden" name="cart_data" id="cart_data_input">
                    <input type="hidden" name="raw_ai_prompt" id="raw_ai_prompt_input">

                    <div class="mb-2">
                        <label class="form-label-pos">1. Customer / Contractor Account</label>
                        <select name="customer_id" id="customer_select" class="form-select form-select-sm" onchange="updateCreditBanner()">
                            <option value="" data-type="WALK_IN" data-limit="0" data-debt="0">Walk-in Customer (Cash Only)</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}"
                                        data-type="{{ $customer->customer_type }}"
                                        data-limit="{{ $customer->credit_limit }}"
                                        data-debt="{{ $customer->current_debt }}">
                                    [{{ $customer->customer_type }}] {{ $customer->name }} (Debt: RM {{ number_format($customer->current_debt, 2) }} / Limit: RM {{ number_format($customer->credit_limit, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <div id="credit_banner" class="small mt-1 d-none p-2 rounded-3"></div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label-pos">2. Payment Term</label>
                            <select name="payment_status" id="payment_status_select" class="form-select form-select-sm" onchange="updateCreditBanner()">
                                <option value="PAID">PAID NOW</option>
                                <option value="CREDIT_HUTANG">HUTANG (Credit)</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label-pos">Method</label>
                            <select name="payment_method" id="payment_method_select" class="form-select form-select-sm">
                                <option value="CASH">CASH</option>
                                <option value="DUITNOW_QR">DUITNOW QR</option>
                                <option value="BANK_TRANSFER">TRANSFER</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label-pos">3. LHDN Mode</label>
                            <select name="invoice_type" class="form-select form-select-sm">
                                <option value="STANDARD_RECEIPT">Standard Receipt</option>
                                <option value="LHDN_EINVOICE">LHDN E-Invoice</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label-pos">4. Fulfillment / Lorry Dispatch</label>
                        <select name="delivery_type" id="delivery_type_select" class="form-select form-select-sm" onchange="toggleDeliveryAddress()">
                            <option value="COUNTER_PICKUP">Counter Self-Pickup</option>
                            <option value="SITE_DELIVERY">Lorry Site Delivery (Generates Unpriced DO)</option>
                        </select>
                        <input type="text" name="delivery_address" id="delivery_address_input" class="form-control form-control-sm mt-2 d-none" placeholder="Enter Site Address & Supervisor Phone for Lorry Driver...">
                    </div>

                    <div class="d-flex justify-content-between align-items-center my-2 pt-2 border-top">
                        <span class="fw-bold text-secondary small">Grand Total</span>
                        <span class="fw-bold fs-4 text-primary" id="cart-total">RM 0.00</span>
                    </div>

                    <button type="submit" id="btn_checkout" class="btn btn-primary w-100 fw-bold py-2">
                        Complete Checkout & Dispatch
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Soft FinTech Popup Modal for AI Parser Warnings & POS Guardrails -->
<div class="modal fade" id="posFeedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 430px;">
        <div class="modal-content border-0 shadow" style="border-radius: 22px;">
            <div class="modal-body p-4 text-center">
                <div id="pos-modal-badge" class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width: 56px; height: 56px; background-color: #FEF3C7; color: #D97706; font-size: 1.5rem;">
                    <i id="pos-modal-icon" class="bi bi-exclamation-triangle-fill"></i>
                </div>

                <h6 class="fw-bold mb-1" id="pos-modal-title">AI Parser Warning</h6>
                <p class="text-muted small mb-3" id="pos-modal-subtitle">
                    Please review the following adjustments made to this order:
                </p>

                <div class="pos-modal-list-box mb-4" id="pos-modal-list"></div>

                <button type="button" id="pos-modal-btn" class="btn btn-primary w-100 fw-bold py-2" data-bs-dismiss="modal">
                    Got It, Continue
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const catalog = @json($products->keyBy('id'));
    let cart = [];
    let currentPosFilter = 'ALL';

    function showPosPopup(type, title, subtitle, itemsHtmlArray) {
        const badge = document.getElementById('pos-modal-badge');
        const icon = document.getElementById('pos-modal-icon');
        const titleEl = document.getElementById('pos-modal-title');
        const subEl = document.getElementById('pos-modal-subtitle');
        const listEl = document.getElementById('pos-modal-list');
        const btnEl = document.getElementById('pos-modal-btn');

        if (type === 'error') {
            badge.style.backgroundColor = '#FEE2E2';
            badge.style.color = '#DC2626';
            icon.className = 'bi bi-shield-exclamation';
            btnEl.className = 'btn btn-danger w-100 fw-bold py-2';
        } else {
            badge.style.backgroundColor = '#FEF3C7';
            badge.style.color = '#D97706';
            icon.className = 'bi bi-exclamation-triangle-fill';
            btnEl.className = 'btn btn-primary w-100 fw-bold py-2';
        }

        titleEl.innerText = title;
        subEl.innerText = subtitle;
        listEl.innerHTML = itemsHtmlArray.map(line => `<div class="pos-modal-item">${line}</div>`).join('');

        const modalEl = document.getElementById('posFeedbackModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function setPosFilter(filterType, btnEl) {
        currentPosFilter = filterType;
        document.querySelectorAll('.filter-pill').forEach(btn => btn.classList.remove('active'));
        btnEl.classList.add('active');
        filterPosCatalog();
    }

    function filterPosCatalog() {
        const query = document.getElementById('pos_search_input').value.toLowerCase().trim();
        const unitFilter = document.getElementById('pos_unit_filter').value;
        const items = document.querySelectorAll('.pos-product-item');
        let visibleCount = 0;

        items.forEach(item => {
            const matchesSearch = !query || item.dataset.name.includes(query) || item.dataset.sku.includes(query);
            const matchesUnit = !unitFilter || item.dataset.unit === unitFilter;
            const matchesPill =
                currentPosFilter === 'ALL' ||
                (currentPosFilter === 'BULK' && item.dataset.bulk === '1') ||
                (currentPosFilter === 'LOW' && item.dataset.low === '1');

            if (matchesSearch && matchesUnit && matchesPill) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });

        document.getElementById('pos_no_results').classList.toggle('d-none', visibleCount > 0);
    }

    function addToCart(productId, forcedQty = 1, forcedMode = 'BASE') {
        const p = catalog[productId];
        if (!p) return;

        let existing = cart.find(item => item.id === productId && item.sale_mode === forcedMode);
        if (existing) {
            existing.qty += forcedQty;
        } else {
            const isBulk = (forcedMode === 'BULK' && p.bulk_unit_type && p.bulk_conversion_rate > 1);
            const baseFloor = parseFloat((p.supplier_cost_price * 1.08).toFixed(2));
            const bulkFloor = isBulk ? parseFloat((p.supplier_cost_price * p.bulk_conversion_rate * 1.08).toFixed(2)) : baseFloor;
            const defaultPrice = isBulk ? parseFloat(p.bulk_selling_price) : parseFloat(p.retail_selling_price);

            cart.push({
                id: p.id,
                name: p.name,
                sale_mode: isBulk ? 'BULK' : 'BASE',
                unit_label: isBulk ? p.bulk_unit_type : p.unit_type,
                price: defaultPrice,
                min_safe_price: isBulk ? bulkFloor : baseFloor,
                qty: forcedQty,
                has_bulk: Boolean(p.bulk_unit_type && p.bulk_conversion_rate > 1)
            });
        }
        updateCartUI();
    }

    function toggleSaleMode(index) {
        let item = cart[index];
        const p = catalog[item.id];
        if (!p || !item.has_bulk) return;

        if (item.sale_mode === 'BASE') {
            item.sale_mode = 'BULK';
            item.unit_label = p.bulk_unit_type;
            item.price = parseFloat(p.bulk_selling_price);
            item.min_safe_price = parseFloat((p.supplier_cost_price * p.bulk_conversion_rate * 1.08).toFixed(2));
        } else {
            item.sale_mode = 'BASE';
            item.unit_label = p.unit_type;
            item.price = parseFloat(p.retail_selling_price);
            item.min_safe_price = parseFloat((p.supplier_cost_price * 1.08).toFixed(2));
        }
        updateCartUI();
    }

    function updateItemQty(index, newQty) {
        let q = parseInt(newQty);
        const p = catalog[cart[index].id];
        if (isNaN(q) || q < 1) q = 1;

        if (p && q > p.stock_quantity) {
            q = p.stock_quantity;
            showPosPopup(
                'warning',
                'Stock Limit Reached',
                'Requested quantity exceeds available warehouse inventory:',
                [`<span class="ai-warn-pill pill-capped">Stock Cap</span> <strong>${p.name}</strong> capped at <strong>${p.stock_quantity} ${p.unit_type}</strong>.`]
            );
        }

        cart[index].qty = q;
        updateCartUI();
    }

    function updateNegotiatedPrice(index, newPrice) {
        let item = cart[index];
        let p = parseFloat(newPrice);
        if (isNaN(p) || p < item.min_safe_price) {
            showPosPopup(
                'error',
                'Floor Margin Guardrail',
                'Negotiated unit price violates the Cost + 8% minimum lock:',
                [`Cannot bargain <strong>${item.name} (${item.unit_label})</strong> below <strong>RM ${item.min_safe_price.toFixed(2)}</strong>.`]
            );
            item.price = item.min_safe_price;
        } else {
            item.price = p;
        }
        updateCartUI();
    }

    function updateCartUI() {
        let container = document.getElementById('cart-items');
        let totalDisplay = document.getElementById('cart-total');
        let total = 0;

        container.innerHTML = '';

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted mt-5 small">
                    <i class="bi bi-cart3 fs-2 d-block mb-2 text-secondary opacity-50"></i>
                    Cart is empty. Click a product or paste a contractor message above.
                </div>`;
        }

        cart.forEach((item, index) => {
            let subtotal = item.price * item.qty;
            total += subtotal;

            let bulkButton = item.has_bulk
                ? `<button type="button" class="unit-switch-btn ms-1" onclick="toggleSaleMode(${index})" title="Switch Base/Bulk Unit">⇄ ${item.unit_label}</button>`
                : `<span class="badge bg-light text-secondary border ms-1" style="font-size: 0.68rem;">${item.unit_label}</span>`;

            container.innerHTML += `
                <div class="cart-row">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold text-truncate small" style="max-width: 72%;">${item.name} ${bulkButton}</div>
                        <div class="fw-bold text-primary small">RM ${subtotal.toFixed(2)}</div>
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col-4">
                            <div class="d-flex align-items-center gap-1">
                                <small class="text-muted" style="font-size: 0.72rem;">Qty</small>
                                <input type="number" class="form-control cart-mini-input" min="1" value="${item.qty}" onchange="updateItemQty(${index}, this.value)">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-1">
                                <small class="text-muted" style="font-size: 0.72rem;" title="Floor: RM ${item.min_safe_price.toFixed(2)}">RM</small>
                                <input type="number" step="0.01" class="form-control cart-mini-input" value="${item.price.toFixed(2)}" onchange="updateNegotiatedPrice(${index}, this.value)">
                            </div>
                        </div>
                        <div class="col-2 text-end">
                            <button type="button" class="btn btn-sm btn-light border text-danger py-0 px-2" onclick="removeFromCart(${index})">
                                <i class="bi bi-x-lg" style="font-size: 0.72rem;"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Floor Lock: RM ${item.min_safe_price.toFixed(2)} / ${item.unit_label}</small>
                </div>
            `;
        });

        totalDisplay.innerText = 'RM ' + total.toFixed(2);
        document.getElementById('cart_data_input').value = JSON.stringify(cart);
        updateCreditBanner(total);
    }

    function updateCreditBanner(currentTotal = null) {
        if (currentTotal === null) {
            currentTotal = cart.reduce((sum, i) => sum + (i.price * i.qty), 0);
        }

        const select = document.getElementById('customer_select');
        const opt = select.options[select.selectedIndex];
        const paymentStatus = document.getElementById('payment_status_select').value;
        const banner = document.getElementById('credit_banner');
        const checkoutBtn = document.getElementById('btn_checkout');

        if (!opt.value) {
            banner.className = 'small mt-1 d-none';
            checkoutBtn.disabled = (paymentStatus === 'CREDIT_HUTANG');
            if (paymentStatus === 'CREDIT_HUTANG') {
                banner.className = 'small mt-2 p-2 rounded-3 bg-danger bg-opacity-10 text-danger fw-bold';
                banner.innerText = 'BLOCKED: Walk-in customers cannot take Hutang (Credit). Select a registered Contractor.';
            }
            return;
        }

        const limit = parseFloat(opt.dataset.limit || 0);
        const debt = parseFloat(opt.dataset.debt || 0);
        const available = limit - debt;

        if (paymentStatus === 'CREDIT_HUTANG') {
            if (currentTotal > available) {
                banner.className = 'small mt-2 p-2 rounded-3 bg-danger bg-opacity-10 text-danger fw-bold';
                banner.innerText = `HUTANG LIMIT EXCEEDED! Remaining Credit: RM ${available.toFixed(2)} | Order: RM ${currentTotal.toFixed(2)}`;
                checkoutBtn.disabled = true;
            } else {
                banner.className = 'small mt-2 p-2 rounded-3 bg-warning bg-opacity-15 text-dark fw-semibold';
                banner.innerText = `Hutang Approved. Available Credit After Order: RM ${(available - currentTotal).toFixed(2)}`;
                checkoutBtn.disabled = false;
            }
        } else {
            banner.className = 'small mt-2 p-2 rounded-3 bg-info bg-opacity-10 text-primary fw-medium';
            banner.innerText = `Current Debt: RM ${debt.toFixed(2)} / Limit: RM ${limit.toFixed(2)}`;
            checkoutBtn.disabled = false;
        }
    }

    function toggleDeliveryAddress() {
        const type = document.getElementById('delivery_type_select').value;
        const addrInput = document.getElementById('delivery_address_input');
        if (type === 'SITE_DELIVERY') {
            addrInput.classList.remove('d-none');
            addrInput.required = true;
        } else {
            addrInput.classList.add('d-none');
            addrInput.required = false;
        }
    }

    async function parseContractorOrder() {
        const input = document.getElementById('ai_order_input');
        const btn = document.getElementById('btn_parse_ai');
        const text = input.value.trim();

        if (!text) {
            showPosPopup(
                'warning',
                'Empty Order Message',
                'Nothing to parse yet:',
                ['Please paste a contractor WhatsApp or Telegram message into the box first.']
            );
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Parsing...';

        try {
            const res = await fetch("{{ route('pos.parse.ai') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ raw_text: text })
            });

            const data = await res.json();
            if (data.status === 'success' && Array.isArray(data.items)) {
                if (data.items.length === 0 && (!data.unmatched || data.unmatched.length === 0)) {
                    showPosPopup(
                        'warning',
                        'No Matching Items Found',
                        'The AI parser could not match any items in that message:',
                        ['No hardware items matched your active inventory catalog.']
                    );
                } else {
                    let warnings = [];
                    let addedCount = 0;

                    data.items.forEach(parsed => {
                        const p = catalog[parsed.product_id];
                        if (!p) return;

                        let requestedQty = parseInt(parsed.qty) || 1;
                        let finalQty = requestedQty;

                        if (requestedQty > p.stock_quantity) {
                            finalQty = p.stock_quantity;
                            warnings.push(`<span class="ai-warn-pill pill-capped">Stock Cap</span> <strong>${p.name}</strong> capped at <strong>${p.stock_quantity}</strong> (Requested ${requestedQty})`);
                        }

                        if (finalQty > 0) {
                            addToCart(parseInt(parsed.product_id), finalQty, parsed.sale_mode || 'BASE');
                            addedCount++;
                        } else {
                            warnings.push(`<span class="ai-warn-pill pill-unmatched">Out of Stock</span> Skipped <strong>${p.name}</strong> (0 remaining)`);
                        }
                    });

                    if (data.unmatched && data.unmatched.length > 0) {
                        const unmatchedList = data.unmatched.map(u => `<strong>${u}</strong>`).join(', ');
                        warnings.push(`<span class="ai-warn-pill pill-unmatched">Not in Inventory</span> Unmatched items: ${unmatchedList}`);
                    }

                    if (warnings.length > 0) {
                        showPosPopup(
                            'warning',
                            'AI Parser Warnings',
                            `${addedCount} available item${addedCount === 1 ? ' was' : 's were'} added to cart with the following notices:`,
                            warnings
                        );
                    }

                    document.getElementById('raw_ai_prompt_input').value = text;
                }
            } else {
                showPosPopup(
                    'error',
                    'AI Parser Error',
                    'Could not process the contractor message:',
                    [data.message || 'Failed to parse contractor order.']
                );
            }
        } catch (err) {
            showPosPopup(
                'error',
                'Connection Error',
                'Failed to reach the AI parser endpoint:',
                [err.message]
            );
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> Parse to Cart';
    }

    function validateCheckout() {
        if (cart.length === 0) {
            showPosPopup(
                'error',
                'Cart is Empty',
                'Cannot complete checkout:',
                ['Please click a product card or parse a contractor message before checking out.']
            );
            return false;
        }
        return true;
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartUI();
    }

    function clearCart() {
        cart = [];
        document.getElementById('raw_ai_prompt_input').value = '';
        updateCartUI();
    }
</script>
@endsection