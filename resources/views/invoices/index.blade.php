@extends('layouts.admin')

@section('content')
<style>
    /* Top Filter & Search Bar */
    .ledger-top-bar {
        background: #FFFFFF;
        border-radius: 20px;
        padding: 0.9rem 1.35rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.025);
    }

    .ledger-search-input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 0.88rem;
        color: #0F172A;
    }

    .ledger-filter-pill {
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

    .ledger-filter-pill.active, .ledger-filter-pill:hover {
        background: #E0F2FE;
        color: #0284C7;
        border-color: #BAE6FD;
    }

    /* Soft FinTech Status Pills */
    .soft-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.73rem;
        font-weight: 700;
        padding: 0.35em 0.75em;
        border-radius: 8px;
    }

    .pill-paid {
        background-color: #DCFCE7;
        color: #15803D;
    }

    .pill-hutang {
        background-color: #FEE2E2;
        color: #DC2626;
    }

    .pill-lorry {
        background-color: #FEF3C7;
        color: #B45309;
    }

    .pill-pickup {
        background-color: #F1F5F9;
        color: #475569;
    }

    .pill-lhdn-ok {
        background-color: #E0F2FE;
        color: #0284C7;
    }

    .pill-lhdn-pending {
        background-color: #F1F5F9;
        color: #64748B;
    }

    .order-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background-color: #F0F9FF;
        color: #0284C7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
</style>

<!-- Header Row -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Order Ledger, E-Invoices & Lorry DO</h4>
        <small class="text-muted">View transactions, LHDN validation status, and print delivery orders</small>
    </div>
    <a href="{{ route('pos') }}" class="btn btn-primary fw-bold">
        <i class="bi bi-plus-lg me-1"></i> New POS Order
    </a>
</div>

<!-- Search & Quick Filter Bar -->
<div class="ledger-top-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 220px;">
        <i class="bi bi-search text-muted"></i>
        <input type="text" id="order_search_input" class="ledger-search-input" placeholder="Search Order ID, customer name, LHDN UIN, or site address..." oninput="filterOrderLedger()">
    </div>
    <div class="d-flex align-items-center gap-1 flex-wrap">
        <button type="button" class="ledger-filter-pill active" onclick="setOrderFilter('ALL', this)">All Orders</button>
        <button type="button" class="ledger-filter-pill" onclick="setOrderFilter('HUTANG', this)">Hutang (Credit)</button>
        <button type="button" class="ledger-filter-pill" onclick="setOrderFilter('LORRY', this)">Lorry Delivery</button>
        <button type="button" class="ledger-filter-pill" onclick="setOrderFilter('LHDN', this)">LHDN Validated</button>
    </div>
</div>

<!-- Main Orders Card -->
<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Order ID & Date</th>
                    <th>Customer / Contractor</th>
                    <th>Payment Term</th>
                    <th>Fulfillment Mode</th>
                    <th>LHDN Status</th>
                    <th class="text-end">Total (RM)</th>
                    <th class="text-end pe-3">Print / View</th>
                </tr>
            </thead>
            <tbody id="order_ledger_body">
                @forelse($orders as $order)
                @php
                    $paddedId = '#' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
                    $customerName = $order->customer ? $order->customer->name : 'Walk-in Customer';
                    $isHutang = $order->payment_status === 'CREDIT_HUTANG';
                    $isLorry = $order->delivery_type === 'SITE_DELIVERY';
                    $isLhdnValid = in_array($order->lhdn_status, ['VALIDATED', 'CONSOLIDATED']);
                @endphp
                <tr class="order-ledger-row"
                    data-search="{{ strtolower($paddedId . ' ' . $customerName . ' ' . ($order->lhdn_uin ?? '') . ' ' . ($order->delivery_address ?? '')) }}"
                    data-hutang="{{ $isHutang ? '1' : '0' }}"
                    data-lorry="{{ $isLorry ? '1' : '0' }}"
                    data-lhdn="{{ $isLhdnValid ? '1' : '0' }}">
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="order-icon-box">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $paddedId }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    {{ $order->created_at->format('d M Y, h:i A') }} • {{ $order->user->name ?? 'N/A' }}
                                </small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($order->customer)
                            <div class="fw-bold small">{{ $order->customer->name }}</div>
                            <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                {{ $order->customer->customer_type }}
                            </span>
                        @else
                            <span class="text-muted small">Walk-in Customer</span>
                        @endif
                    </td>
                    <td>
                        <span class="soft-pill {{ $isHutang ? 'pill-hutang' : 'pill-paid' }}">
                            {{ $isHutang ? 'HUTANG (Credit)' : 'PAID (' . $order->payment_method . ')' }}
                        </span>
                    </td>
                    <td>
                        @if($isLorry)
                            <span class="soft-pill pill-lorry"><i class="bi bi-truck"></i> SITE DELIVERY</span>
                            <small class="text-muted text-truncate d-block mt-1" style="max-width: 185px; font-size: 0.74rem;" title="{{ $order->delivery_address }}">
                                {{ $order->delivery_address }}
                            </small>
                        @else
                            <span class="soft-pill pill-pickup">COUNTER PICKUP</span>
                        @endif
                    </td>
                    <td>
                        <span class="soft-pill {{ $isLhdnValid ? 'pill-lhdn-ok' : 'pill-lhdn-pending' }}">
                            {{ $order->lhdn_status }}
                        </span>
                        @if($order->lhdn_uin)
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">{{ $order->lhdn_uin }}</small>
                        @endif
                    </td>
                    <td class="text-end fw-bold text-primary">
                        RM {{ number_format($order->total_amount, 2) }}
                    </td>
                    <td class="text-end pe-3">
                        <a href="{{ route('invoice.show', $order->id) }}" class="btn btn-sm btn-light border text-dark fw-semibold">
                            <i class="bi bi-printer text-primary me-1"></i> Invoice / DO
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted small">No orders recorded yet.</td>
                </tr>
                @endforelse

                <tr id="order_no_results" class="d-none">
                    <td colspan="7" class="text-center py-5 text-muted small">No matching orders found.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    let currentOrderFilter = 'ALL';

    function setOrderFilter(type, btnEl) {
        currentOrderFilter = type;
        document.querySelectorAll('.ledger-filter-pill').forEach(b => b.classList.remove('active'));
        btnEl.classList.add('active');
        filterOrderLedger();
    }

    function filterOrderLedger() {
        const query = document.getElementById('order_search_input').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.order-ledger-row');
        let visible = 0;

        rows.forEach(row => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const matchesFilter =
                currentOrderFilter === 'ALL' ||
                (currentOrderFilter === 'HUTANG' && row.dataset.hutang === '1') ||
                (currentOrderFilter === 'LORRY' && row.dataset.lorry === '1') ||
                (currentOrderFilter === 'LHDN' && row.dataset.lhdn === '1');

            if (matchesSearch && matchesFilter) {
                row.classList.remove('d-none');
                visible++;
            } else {
                row.classList.add('d-none');
            }
        });

        const noRes = document.getElementById('order_no_results');
        if (noRes && rows.length > 0) {
            noRes.classList.toggle('d-none', visible > 0);
        }
    }

    // Automatically apply search query if redirected from the Dashboard search bar
    document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const searchParam = params.get('search');
        if (searchParam) {
            const input = document.getElementById('order_search_input');
            input.value = searchParam;
            filterOrderLedger();
        }
    });
</script>
@endsection