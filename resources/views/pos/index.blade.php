@extends('layouts.admin')

@section('content')
<div class="row h-100">
    <div class="col-md-7 h-100 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h3 class="fw-bold mb-0"><i class="bi bi-cart-check-fill me-2"></i> SME Hardware POS</h3>
            <span class="badge bg-dark">Floor Lock: Cost + 8% Active</span>
        </div>

        @if(session('success'))
            <div class="alert alert-success fw-bold py-2">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger fw-bold py-2">{{ session('error') }}</div>
        @endif

        <div class="card border-primary shadow-sm mb-3">
            <div class="card-body p-2 bg-light">
                <label class="form-label fw-bold small text-primary mb-1">
                    <i class="bi bi-whatsapp me-1"></i> AI Contractor WhatsApp / Telegram Order Parser
                </label>
                <div class="input-group input-group-sm">
                    <input type="text" id="ai_order_input" class="form-control" placeholder="e.g. Boss hantar 10 beg simen YTL, 2 kotak paku pergi site...">
                    <button class="btn btn-primary fw-bold" type="button" id="btn_parse_ai" onclick="parseContractorOrder()">
                        <i class="bi bi-lightning-charge-fill"></i> Parse to Cart
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-2 overflow-auto flex-grow-1 pb-3">
            @foreach($products as $product)
            <div class="col-md-4 col-sm-6 mb-2">
                <div class="card h-100 border shadow-sm product-card" onclick="addToCart({{ $product->id }})" style="cursor: pointer;">
                    <div class="position-relative" style="height: 110px; background-color: #f8fafc; overflow: hidden;">
                        @if($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <i class="bi bi-box-seam text-secondary" style="font-size: 2.5rem; opacity: 0.3;"></i>
                            </div>
                        @endif
                        <span class="position-absolute top-0 end-0 badge {{ $product->stock_quantity > $product->low_stock_threshold ? 'bg-success' : 'bg-danger' }} m-1 shadow-sm">
                            {{ $product->stock_quantity }} {{ $product->unit_type }}
                        </span>
                    </div>

                    <div class="card-body p-2">
                        <h6 class="card-title fw-bold mb-1 text-truncate" title="{{ $product->name }}">{{ $product->name }}</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">{{ $product->sku }}</small>
                            <span class="text-primary fw-bold small">RM {{ number_format($product->retail_selling_price, 2) }}/{{ $product->unit_type }}</span>
                        </div>
                        @if($product->bulk_unit_type && $product->bulk_conversion_rate)
                            <div class="mt-1">
                                <span class="badge bg-info text-dark w-100 text-truncate">
                                    {{ $product->bulk_unit_type }} ({{ $product->bulk_conversion_rate }}): RM {{ number_format($product->bulk_selling_price, 2) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="col-md-5 h-100 border-start bg-white shadow-sm p-0 d-flex flex-column">
        <div class="p-2 px-3 bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
            <span>Current Order & Bargaining</span>
            <button class="btn btn-sm btn-outline-light py-0" onclick="clearCart()">Clear</button>
        </div>

        <div class="flex-grow-1 p-3 overflow-auto" id="cart-items">
            <div class="text-center text-muted mt-4">Cart is empty. Click a product or paste a contractor message above.</div>
        </div>

        <div class="p-3 border-top bg-light">
            <form action="{{ route('pos.checkout') }}" method="POST" id="checkout-form" onsubmit="return validateCheckout()">
                @csrf
                <input type="hidden" name="cart_data" id="cart_data_input">
                <input type="hidden" name="raw_ai_prompt" id="raw_ai_prompt_input">

                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">1. Customer / Contractor Account</label>
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
                    <div id="credit_banner" class="small mt-1 d-none p-1 rounded"></div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <label class="form-label small fw-bold mb-1">2. Payment Term</label>
                        <select name="payment_status" id="payment_status_select" class="form-select form-select-sm" onchange="updateCreditBanner()">
                            <option value="PAID">PAID NOW</option>
                            <option value="CREDIT_HUTANG">HUTANG (Credit)</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold mb-1">Method</label>
                        <select name="payment_method" id="payment_method_select" class="form-select form-select-sm">
                            <option value="CASH">CASH</option>
                            <option value="DUITNOW_QR">DUITNOW QR</option>
                            <option value="BANK_TRANSFER">TRANSFER</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-bold mb-1">3. LHDN Mode</label>
                        <select name="invoice_type" class="form-select form-select-sm">
                            <option value="STANDARD_RECEIPT">Standard Receipt</option>
                            <option value="LHDN_EINVOICE">LHDN E-Invoice</option>
                        </select>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">4. Fulfillment / Lorry Dispatch</label>
                    <select name="delivery_type" id="delivery_type_select" class="form-select form-select-sm" onchange="toggleDeliveryAddress()">
                        <option value="COUNTER_PICKUP">Counter Self-Pickup</option>
                        <option value="SITE_DELIVERY">Lorry Site Delivery (Generates Unpriced DO)</option>
                    </select>
                    <input type="text" name="delivery_address" id="delivery_address_input" class="form-control form-control-sm mt-1 d-none" placeholder="Enter Site Address & Supervisor Phone for Lorry Driver...">
                </div>

                <div class="d-flex justify-content-between align-items-center my-2 pt-2 border-top">
                    <span class="fw-bold">Grand Total</span>
                    <span class="fw-bold fs-4 text-primary" id="cart-total">RM 0.00</span>
                </div>

                <button type="submit" id="btn_checkout" class="btn btn-success w-100 fw-bold py-2">
                    Complete Checkout & Dispatch
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const catalog = @json($products->keyBy('id'));
    let cart = [];

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
        if (isNaN(q) || q < 1) q = 1;
        cart[index].qty = q;
        updateCartUI();
    }

    function updateNegotiatedPrice(index, newPrice) {
        let item = cart[index];
        let p = parseFloat(newPrice);
        if (isNaN(p) || p < item.min_safe_price) {
            alert(`FLOOR MARGIN GUARDRAIL: Cannot bargain ${item.name} (${item.unit_label}) below RM ${item.min_safe_price.toFixed(2)} (Supplier Cost + 8%).`);
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
            container.innerHTML = '<div class="text-center text-muted mt-4">Cart is empty. Click a product or paste a contractor message above.</div>';
        }

        cart.forEach((item, index) => {
            let subtotal = item.price * item.qty;
            total += subtotal;

            let bulkButton = item.has_bulk
                ? `<button type="button" class="btn btn-sm btn-outline-info py-0 px-1 ms-1" onclick="toggleSaleMode(${index})" title="Switch Base/Bulk Unit">⇄ ${item.unit_label}</button>`
                : `<span class="badge bg-secondary ms-1">${item.unit_label}</span>`;

            container.innerHTML += `
                <div class="border-bottom pb-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="fw-bold text-truncate" style="max-width: 70%;">${item.name} ${bulkButton}</div>
                        <div class="fw-bold text-primary">RM ${subtotal.toFixed(2)}</div>
                    </div>
                    <div class="row g-1 align-items-center">
                        <div class="col-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Qty</span>
                                <input type="number" class="form-control" min="1" value="${item.qty}" onchange="updateItemQty(${index}, this.value)">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" title="Floor: RM ${item.min_safe_price.toFixed(2)}">RM</span>
                                <input type="number" step="0.01" class="form-control" value="${item.price.toFixed(2)}" onchange="updateNegotiatedPrice(${index}, this.value)">
                            </div>
                        </div>
                        <div class="col-2 text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeFromCart(${index})">X</button>
                        </div>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">Floor Lock: RM ${item.min_safe_price.toFixed(2)} / ${item.unit_label}</small>
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
                banner.className = 'small mt-1 p-1 rounded bg-danger text-white fw-bold';
                banner.innerText = 'BLOCKED: Walk-in customers cannot take Hutang (Credit). Select a registered Contractor.';
            }
            return;
        }

        const limit = parseFloat(opt.dataset.limit || 0);
        const debt = parseFloat(opt.dataset.debt || 0);
        const available = limit - debt;

        if (paymentStatus === 'CREDIT_HUTANG') {
            if (currentTotal > available) {
                banner.className = 'small mt-1 p-1 rounded bg-danger text-white fw-bold';
                banner.innerText = `HUTANG LIMIT EXCEEDED! Remaining Credit: RM ${available.toFixed(2)} | Order: RM ${currentTotal.toFixed(2)}`;
                checkoutBtn.disabled = true;
            } else {
                banner.className = 'small mt-1 p-1 rounded bg-warning text-dark fw-bold';
                banner.innerText = `Hutang Approved. Available Credit After Order: RM ${(available - currentTotal).toFixed(2)}`;
                checkoutBtn.disabled = false;
            }
        } else {
            banner.className = 'small mt-1 p-1 rounded bg-info text-dark';
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
        if (!text) return alert('Paste a contractor order message first.');

        btn.disabled = true;
        btn.innerHTML = 'Parsing...';

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
                if (data.items.length === 0) {
                    alert('No matching active hardware SKUs found in that message.');
                } else {
                    data.items.forEach(parsed => {
                        addToCart(parseInt(parsed.product_id), parseInt(parsed.qty) || 1, parsed.sale_mode || 'BASE');
                    });
                    document.getElementById('raw_ai_prompt_input').value = text;
                }
            } else {
                alert(data.message || 'Failed to parse contractor order.');
            }
        } catch (err) {
            alert('Error calling AI parser: ' + err.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Parse to Cart';
    }

    function validateCheckout() {
        if (cart.length === 0) {
            alert('Cart is empty!');
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