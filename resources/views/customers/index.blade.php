@extends('layouts.admin')

@section('content')
<style>
    /* Top Search & Filter Bar */
    .customer-top-bar {
        background: #FFFFFF;
        border-radius: 20px;
        padding: 0.9rem 1.35rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.025);
    }

    .customer-search-input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 0.88rem;
        color: #0F172A;
    }

    .customer-filter-pill {
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

    .customer-filter-pill.active, .customer-filter-pill:hover {
        background: #E0F2FE;
        color: #0284C7;
        border-color: #BAE6FD;
    }

    /* Avatar & Soft Pills */
    .customer-avatar {
        width: 42px;
        height: 42px;
        border-radius: 13px;
        background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
        color: #0284C7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .soft-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.73rem;
        font-weight: 700;
        padding: 0.35em 0.75em;
        border-radius: 8px;
    }

    .pill-contractor {
        background-color: #E0F2FE;
        color: #0284C7;
    }

    .pill-walkin {
        background-color: #F1F5F9;
        color: #64748B;
    }

    .pill-loyalty {
        background-color: #FEF3C7;
        color: #B45309;
    }

    .pill-debt-alert {
        background-color: #FEE2E2;
        color: #DC2626;
    }

    .pill-clear {
        background-color: #DCFCE7;
        color: #15803D;
    }

    /* Mini Credit Progress Bar */
    .credit-progress {
        height: 5px;
        background-color: #F1F5F9;
        border-radius: 999px;
        overflow: hidden;
        width: 130px;
        margin-top: 5px;
    }

    .credit-progress-bar {
        height: 100%;
        border-radius: 999px;
    }
</style>

<!-- Header Row -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Contractors, Credit Limits & Loyalty</h4>
        <small class="text-muted">Manage B2B credit exposure, uncollected Hutang, and loyalty points</small>
    </div>
    @if(Auth::user()->role === 'OWNER')
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
        <i class="bi bi-plus-lg me-1"></i> Register Customer / Contractor
    </button>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3">
        <i class="bi bi-exclamation-circle-fill me-1"></i> {{ $errors->first() }}
    </div>
@endif

<!-- Search & Quick Filter Bar -->
<div class="customer-top-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 220px;">
        <i class="bi bi-search text-muted"></i>
        <input type="text" id="customer_search_input" class="customer-search-input" placeholder="Search company name, phone number, or email..." oninput="filterCustomers()">
    </div>
    <div class="d-flex align-items-center gap-1 flex-wrap">
        <button type="button" class="customer-filter-pill active" onclick="setCustomerFilter('ALL', this)">All Accounts</button>
        <button type="button" class="customer-filter-pill" onclick="setCustomerFilter('CONTRACTOR', this)">Contractors</button>
        <button type="button" class="customer-filter-pill" onclick="setCustomerFilter('DEBT', this)">Active Hutang</button>
        <button type="button" class="customer-filter-pill" onclick="setCustomerFilter('WALK_IN', this)">Walk-in</button>
    </div>
</div>

<!-- Main Customers Table Card -->
<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Customer / Company Name</th>
                    <th>Account Type</th>
                    <th>Hutang (Current Debt)</th>
                    <th>Credit Limit</th>
                    <th>Loyalty Points</th>
                    <th class="text-end pe-3">Credit Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                @php
                    $isContractor = $customer->customer_type === 'CONTRACTOR';
                    $hasDebt = $customer->current_debt > 0;
                    $availableCredit = max(0, $customer->credit_limit - $customer->current_debt);
                    $usagePct = $customer->credit_limit > 0 ? min(100, round(($customer->current_debt / $customer->credit_limit) * 100)) : 0;
                @endphp
                <tr class="customer-row"
                    data-search="{{ strtolower($customer->name . ' ' . $customer->phone . ' ' . ($customer->email ?? '')) }}"
                    data-type="{{ $customer->customer_type }}"
                    data-debt="{{ $hasDebt ? '1' : '0' }}">
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="customer-avatar">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $customer->name }}</div>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-telephone me-1"></i>{{ $customer->phone }}
                                    @if($customer->email)
                                        <span class="mx-1">•</span>{{ $customer->email }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="soft-pill {{ $isContractor ? 'pill-contractor' : 'pill-walkin' }}">
                            {{ $customer->customer_type }}
                        </span>
                    </td>
                    <td>
                        @if($hasDebt)
                            <span class="fw-bold text-danger">RM {{ number_format($customer->current_debt, 2) }}</span>
                        @else
                            <span class="text-muted small">RM 0.00</span>
                        @endif
                    </td>
                    <td>
                        @if($isContractor)
                            <div class="fw-bold small">RM {{ number_format($customer->credit_limit, 2) }}</div>
                            <div class="credit-progress">
                                <div class="credit-progress-bar" style="width: {{ $usagePct }}%; background-color: {{ $usagePct > 80 ? '#EF4444' : '#38BDF8' }};"></div>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                Available: <strong class="text-success">RM {{ number_format($availableCredit, 2) }}</strong>
                            </small>
                        @else
                            <span class="text-muted small">Cash Only</span>
                        @endif
                    </td>
                    <td>
                        <span class="soft-pill pill-loyalty">
                            <i class="bi bi-star-fill"></i> {{ $customer->loyalty_points }} pts
                        </span>
                    </td>
                    <td class="text-end pe-3">
                        @if($hasDebt && Auth::user()->role === 'OWNER')
                            <button class="btn btn-sm btn-primary fw-semibold"
                                    type="button"
                                    onclick="openRepayModal('{{ route('customers.repay', $customer->id) }}', '{{ addslashes($customer->name) }}', {{ $customer->current_debt }})">
                                <i class="bi bi-cash-coin me-1"></i> Settle Hutang
                            </button>
                        @elseif($hasDebt)
                            <span class="soft-pill pill-debt-alert">Owner Approval Req.</span>
                        @else
                            <span class="soft-pill pill-clear"><i class="bi bi-check2"></i> Clear</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted small">No customer accounts registered yet.</td>
                </tr>
                @endforelse

                <tr id="customer_no_results" class="d-none">
                    <td colspan="6" class="text-center py-5 text-muted small">No matching customer or contractor accounts found.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@if(Auth::user()->role === 'OWNER')
<!-- Modal 1: Settle Contractor Hutang -->
<div class="modal fade" id="repayHutangModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <form id="repayHutangForm" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Settle Contractor Hutang</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-3 rounded-4 mb-3" style="background-color: #F8FAFC; border: 1px solid #EEF2F6;">
                        <small class="text-muted d-block" style="font-size: 0.74rem;">CONTRACTOR ACCOUNT</small>
                        <div class="fw-bold text-dark mb-2" id="repayCustomerName">-</div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="text-muted small">Outstanding Debt</span>
                            <span class="fw-bold text-danger" id="repayCurrentDebt">RM 0.00</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Amount Paid (RM)</label>
                        <input type="number" step="0.01" name="repayment_amount" id="repayAmountInput" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Register Customer / Contractor -->
<div class="modal fade" id="newCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Register Customer / Contractor</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name / Construction Company</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Binaan Maju Jaya Enterprise" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="012-3456789" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Email (Optional)</label>
                            <input type="email" name="email" class="form-control" placeholder="billing@company.com">
                        </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            <i class="bi bi-telegram text-info me-1"></i>Telegram Chat ID (Optional - For Auto E-Invoice)
                        </label>
                        <input type="text" name="telegram_chat_id" class="form-control" placeholder="e.g. 819203948 (Customer sends /start to bot)">
                    </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label small fw-semibold">Account Tier</label>
                            <select name="customer_type" id="new_customer_type" class="form-select" required
                                    onchange="document.getElementById('new_credit_limit').value = this.value === 'WALK_IN' ? '0.00' : '5000.00';">
                                <option value="CONTRACTOR">CONTRACTOR (Credit Allowed)</option>
                                <option value="WALK_IN">WALK-IN (Cash Only)</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold">Credit Limit (RM)</label>
                            <input type="number" step="0.01" name="credit_limit" id="new_credit_limit" class="form-control" value="5000.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
    let currentCustomerFilter = 'ALL';

    function setCustomerFilter(type, btnEl) {
        currentCustomerFilter = type;
        document.querySelectorAll('.customer-filter-pill').forEach(b => b.classList.remove('active'));
        btnEl.classList.add('active');
        filterCustomers();
    }

    function filterCustomers() {
        const query = document.getElementById('customer_search_input').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.customer-row');
        let visible = 0;

        rows.forEach(row => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const matchesFilter =
                currentCustomerFilter === 'ALL' ||
                (currentCustomerFilter === 'CONTRACTOR' && row.dataset.type === 'CONTRACTOR') ||
                (currentCustomerFilter === 'WALK_IN' && row.dataset.type === 'WALK_IN') ||
                (currentCustomerFilter === 'DEBT' && row.dataset.debt === '1');

            if (matchesSearch && matchesFilter) {
                row.classList.remove('d-none');
                visible++;
            } else {
                row.classList.add('d-none');
            }
        });

        const noRes = document.getElementById('customer_no_results');
        if (noRes && rows.length > 0) {
            noRes.classList.toggle('d-none', visible > 0);
        }
    }

    function openRepayModal(actionUrl, customerName, currentDebt) {
        document.getElementById('repayHutangForm').action = actionUrl;
        document.getElementById('repayCustomerName').innerText = customerName;
        document.getElementById('repayCurrentDebt').innerText = 'RM ' + parseFloat(currentDebt).toFixed(2);

        const input = document.getElementById('repayAmountInput');
        input.max = currentDebt;
        input.value = parseFloat(currentDebt).toFixed(2);

        const modal = new bootstrap.Modal(document.getElementById('repayHutangModal'));
        modal.show();
    }
</script>
@endsection