@extends('layouts.admin')

@section('content')
<style>
    /* Top Header Pill Bar */
    .top-pill-bar {
        background: #FFFFFF;
        border-radius: 20px;
        padding: 0.85rem 1.5rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.025);
    }

    .search-clean-input {
        border: none;
        background: transparent;
        outline: none;
        width: 100%;
        font-size: 0.9rem;
        color: #0F172A;
    }

    /* Soft KPI Cards */
    .soft-kpi-card {
        background: #FFFFFF;
        border-radius: 20px;
        padding: 1.35rem 1.4rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.025);
        height: 100%;
    }

    .soft-kpi-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, #E0F2FE 0%, #BAE6FD 100%);
        color: #0284C7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    /* Royal Blue Feature Card (Used for LHDN & Groq AI) */
    .royal-feature-card {
        background: linear-gradient(135deg, #1E3A8A 0%, #1D4ED8 65%, #2563EB 100%);
        border-radius: 22px;
        padding: 1.6rem;
        color: #FFFFFF;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(29, 78, 216, 0.22);
    }

    .royal-feature-card::after {
        content: '';
        position: absolute;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.07);
        bottom: -50px;
        right: -30px;
        pointer-events: none;
    }

    /* Right-Hand White Summary Panel */
    .right-summary-panel {
        background: #FFFFFF;
        border-radius: 24px;
        padding: 1.75rem 1.5rem;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.03);
        height: 100%;
    }

    /* Borderless List Rows (Replaces Cluttered Tables) */
    .clean-list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.9rem 0;
        border-bottom: 1px solid #F1F5F9;
    }

    .clean-list-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .row-icon-soft {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background-color: #E0F2FE;
        color: #0284C7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .row-icon-alert {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background-color: #FEE2E2;
        color: #DC2626;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    /* Key-Value Summary Rows */
    .kv-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem 0;
        font-size: 0.85rem;
    }

    /* Mini Sub-Cards for Audit Log */
    .audit-mini-card {
        border: 1px solid #F1F5F9;
        border-radius: 16px;
        padding: 0.8rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.6rem;
    }

    /* AI Output FinTech Card Formatting */
    .ai-insight-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
    }

    .ai-Mini-card {
        background-color: #F8FAFC;
        border: 1px solid #EEF2F6;
        border-radius: 16px;
        padding: 1.15rem;
        height: 100%;
    }

    .ai-mini-header {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 0.85rem;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid #E2E8F0;
    }

    .ai-mini-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .ai-mini-card ul {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .ai-mini-card ul li {
        position: relative;
        padding-left: 1.1rem;
        margin-bottom: 0.55rem;
        font-size: 0.82rem;
        color: #334155;
        line-height: 1.5;
    }

    .ai-mini-card ul li:last-child {
        margin-bottom: 0;
    }

    .ai-mini-card ul li::before {
        content: '•';
        position: absolute;
        left: 0;
        top: 0;
        color: #2563EB;
        font-weight: 800;
    }
</style>

@if(session('success'))
    <div class="alert alert-success border-0 rounded-4 shadow-sm mb-3 py-2 px-3 small fw-semibold">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-3 py-2 px-3 small fw-semibold">
        <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
    </div>
@endif

<!-- Top Search & Quick Action Pill Bar -->
<div class="top-pill-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2 flex-grow-1">
        <i class="bi bi-search text-muted"></i>
        <input type="text" class="search-clean-input" placeholder="Search invoices, SKU, or contractor accounts..." onkeypress="if(event.key === 'Enter') window.location.href='{{ route('orders.index') }}?search=' + this.value">
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('pos') }}" class="btn btn-sm btn-light border fw-semibold text-dark">
            <i class="bi bi-bag-check text-primary me-1"></i> Open POS
        </a>
        <a href="{{ route('dashboard.export.csv') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-download me-1"></i> Export CSV Ledger
        </a>
    </div>
</div>

<!-- 4 Clean KPI Cards (Real Hardware Store Metrics) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="soft-kpi-card">
            <div class="soft-kpi-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0" style="font-size: 1.2rem;">RM {{ number_format($totalRevenue, 2) }}</h4>
                <small class="text-muted" style="font-size: 0.76rem;">Gross Revenue ({{ $todayOrders }} today)</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="soft-kpi-card">
            <div class="soft-kpi-icon">
                <i class="bi bi-calculator"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0" style="font-size: 1.2rem;">RM {{ number_format($totalCogs + $totalExpenses, 2) }}</h4>
                <small class="text-muted" style="font-size: 0.76rem;">Total COGS + OpEx</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="soft-kpi-card">
            <div class="soft-kpi-icon">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 {{ $netProfit >= 0 ? 'text-dark' : 'text-danger' }}" style="font-size: 1.2rem;">
                    RM {{ number_format($netProfit, 2) }}
                </h4>
                <small class="text-muted" style="font-size: 0.76rem;">True Net Profit</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="soft-kpi-card">
            <div class="soft-kpi-icon">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-danger" style="font-size: 1.2rem;">RM {{ number_format($totalHutang, 2) }}</h4>
                <small class="text-muted" style="font-size: 0.76rem;">Uncollected Hutang ({{ $debtorsCount }})</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT WORKSPACE COLUMN (8 COLS) -->
    <div class="col-xl-8 d-flex flex-column gap-4">

        <!-- 1. Shop Operating Expenses (Clean List + Modal Trigger) -->
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="fw-bold mb-0">Shop Operating Expenses</h6>
                    <small class="text-muted">Lorry fuel, staff wages, rent & utilities deducted from profit</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                    <i class="bi bi-plus-lg me-1"></i> Log Expense
                </button>
            </div>

            <div class="d-flex align-items-baseline gap-2 my-2 pb-2 border-bottom">
                <h4 class="fw-bold mb-0">RM {{ number_format($totalExpenses, 2) }}</h4>
                <small class="text-muted">Cumulative OpEx</small>
            </div>

            <div>
                @forelse($recentExpenses as $exp)
                @php
                    [$expIcon, $expBg, $expColor] = match(strtoupper($exp->category)) {
                        'WAGES', 'SALARY', 'STAFF_WAGES'   => ['bi-person-check-fill', '#DBEAFE', '#1D4ED8'],
                        'UTILITIES', 'ELECTRICITY', 'WATER'=> ['bi-lightning-charge-fill', '#FEF3C7', '#B45309'],
                        'RENT', 'SHOP_RENT'                => ['bi-shop', '#F3E8FF', '#7E22CE'],
                        'MAINTENANCE', 'LORRY_MAINTENANCE' => ['bi-wrench-adjustable', '#E0F2FE', '#0284C7'],
                        'OTHER', 'SUPPLIES', 'MISC'        => ['bi-receipt-cutoff', '#F1F5F9', '#475569'],
                        default                            => ['bi-fuel-pump-fill', '#FEE2E2', '#DC2626'],
                    };
                @endphp
                <div class="clean-list-row">
                    <div class="d-flex align-items-center gap-3">
                        <div class="row-icon-soft" style="background-color: {{ $expBg }}; color: {{ $expColor }};">
                            <i class="bi {{ $expIcon }}"></i>
                        </div>
                        <div>
                            <div class="fw-bold small">{{ $exp->description }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                {{ $exp->category }} • {{ \Carbon\Carbon::parse($exp->expense_date ?? $exp->created_at)->format('d M Y') }}
                            </small>
                        </div>
                    </div>
                    <div class="fw-bold small text-danger">
                        -RM {{ number_format($exp->amount, 2) }}
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4 small">
                    No operating expenses recorded yet.
                </div>
                @endforelse
            </div>
        </div>

        <!-- 2. Bottom Split: Low Stock Replenishment Alerts + Groq AI Intelligence -->
        <div class="row g-4">
            <!-- Low Stock Alerts List -->
            <div class="col-lg-7">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0">Low Stock Replenishment</h6>
                            <small class="text-muted">Items below safety threshold</small>
                        </div>
                        <a href="{{ route('products.index') }}" class="btn btn-sm btn-light border text-secondary">
                            GRN Stock-In
                        </a>
                    </div>

                    <div>
                        @forelse($lowStockProducts as $prod)
                        <div class="clean-list-row">
                            <div class="d-flex align-items-center gap-3">
                                <div class="row-icon-alert">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">{{ $prod->name }}</div>
                                    <small class="text-muted" style="font-size: 0.74rem;">
                                        SKU: {{ $prod->sku }} • Min: {{ $prod->low_stock_threshold }} {{ $prod->base_unit }}
                                    </small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold small text-danger d-block">{{ $prod->stock_quantity }} {{ $prod->base_unit }}</span>
                                <small class="text-muted" style="font-size: 0.7rem;">Remaining</small>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-success small fw-semibold">
                            <i class="bi bi-check-circle-fill me-1"></i> All hardware stock levels are healthy.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Royal Blue Groq AI Feature Card -->
            <div class="col-lg-5">
                <div class="royal-feature-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <span class="badge bg-white bg-opacity-25 text-white mb-2">GROQ AI ENGINE</span>
                        <h5 class="fw-bold mb-2" style="line-height: 1.35;">
                            Predictive Stock & Contractor Intelligence
                        </h5>
                        <p class="small text-white text-opacity-75 mb-3">
                            Generate live restocking recommendations, contractor lifecycle predictions, and dead-stock bundles.
                        </p>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mt-2">
                        <button type="button" id="btn-generate-ai" class="btn btn-sky px-3 py-2 shadow-sm">
                            Generate Analysis
                        </button>
                        <i class="bi bi-stars text-white text-opacity-25" style="font-size: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Groq AI Output Card (Appears cleanly below when triggered) -->
        <div id="ai-loading" class="card p-4 text-center d-none">
            <div class="spinner-border text-primary mx-auto" role="status"></div>
            <p class="mt-2 mb-0 text-muted small">Analyzing Senawang inventory & contractor order history...</p>
        </div>

        <div id="ai-insights-container" class="card p-4 d-none"></div>

    </div>

    <!-- RIGHT SUMMARY PANEL (4 COLS) -->
    <div class="col-xl-4">
        <div class="right-summary-panel d-flex flex-column justify-content-between">
            <div>
                <!-- Owner Profile Header -->
                <div class="d-flex align-items-center gap-3 mb-4 pb-2 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-primary"
                         style="width: 48px; height: 48px; background-color: #E0F2FE; font-size: 1.1rem;">
                        {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.98rem;">Hello, {{ Auth::user()->name ?? 'Owner' }}</div>
                        <small class="text-muted" style="font-size: 0.78rem;">Senawang Branch • {{ Auth::user()->role ?? 'OWNER' }}</small>
                    </div>
                </div>

                <!-- LHDN MyInvois Consolidation Hub -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small">LHDN MyInvois Consolidation</span>
                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">Class Code 022</span>
                </div>

                <div class="royal-feature-card mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-white text-opacity-75 fw-medium">Pending Walk-In Value</span>
                        <i class="bi bi-patch-check-fill text-info fs-5"></i>
                    </div>
                    <h3 class="fw-bold mb-3">RM {{ number_format($pendingLhdnAmount, 2) }}</h3>

                    <div class="d-flex justify-content-between pt-2 border-top border-white border-opacity-25 small">
                        <div>
                            <small class="text-white text-opacity-50 d-block" style="font-size: 0.68rem;">QUEUED RECEIPTS</small>
                            <span class="fw-bold">{{ $pendingLhdnCount }} Walk-ins</span>
                        </div>
                        <div class="text-end">
                            <small class="text-white text-opacity-50 d-block" style="font-size: 0.68rem;">LHDN VALIDATED</small>
                            <span class="fw-bold">{{ $validatedLhdnCount }} Invoices</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('dashboard.lhdn.consolidate') }}" method="POST" class="mb-4">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 py-2" {{ $pendingLhdnCount === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-cloud-check me-1"></i> Consolidate & Submit {{ $pendingLhdnCount }} Receipts
                    </button>
                </form>

                <!-- Financial Breakdown (Clean Key-Value Rows) -->
                <div class="fw-bold small mb-2">Profit & Margin Breakdown</div>
                <div class="mb-4 pb-2 border-bottom">
                    <div class="kv-summary-row">
                        <span class="text-muted">Today's Revenue ({{ $todayOrders }} orders)</span>
                        <span class="fw-bold text-primary">+RM {{ number_format($todayRevenue, 2) }}</span>
                    </div>
                    <div class="kv-summary-row">
                        <span class="text-muted">Cost of Goods Sold (COGS)</span>
                        <span class="fw-bold">RM {{ number_format($totalCogs, 2) }}</span>
                    </div>
                    <div class="kv-summary-row">
                        <span class="text-muted">Gross Profit Margin</span>
                        <span class="fw-bold">RM {{ number_format($grossProfit, 2) }}</span>
                    </div>
                    <div class="kv-summary-row">
                        <span class="text-muted">Operating Expenses</span>
                        <span class="fw-bold text-danger">-RM {{ number_format($totalExpenses, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Staff Shift & Security Audit Log -->
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold small">Recent Staff Shift Logins</span>
                    <small class="text-muted" style="font-size: 0.72rem;">Audit Trail</small>
                </div>

                @forelse($recentLogins->take(4) as $log)
                <div class="audit-mini-card">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                             style="width: 32px; height: 32px; background: #F1F5F9; color: #0F172A; font-size: 0.78rem;">
                            {{ substr($log->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size: 0.82rem;">{{ $log->name }}</div>
                            <small class="text-muted" style="font-size: 0.7rem;">
                                {{ $log->role }} • {{ \Carbon\Carbon::parse($log->login_at)->format('d M, h:i A') }}
                            </small>
                        </div>
                    </div>
                    <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">{{ $log->ip_address }}</span>
                </div>
                @empty
                <div class="text-muted small text-center py-2">No shift logins recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal: Log Shop Operating Expense -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Log Shop Operating Expense</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('dashboard.expenses.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Expense Category</label>
                        <select name="category" class="form-select" required>
                            <option value="TRANSPORT_FUEL">Lorry Fuel / Diesel</option>
                            <option value="WAGES">Staff Wages</option>
                            <option value="RENT">Shop Rent</option>
                            <option value="UTILITIES">Electricity / Water</option>
                            <option value="MAINTENANCE">Lorry / Forklift Repair</option>
                            <option value="OTHER">Other Expense</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Amount (RM)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Date</label>
                            <input type="date" name="expense_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Description / Reference</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., Petronas Diesel Lorry" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-generate-ai')?.addEventListener('click', function () {
    const btn = this;
    const loading = document.getElementById('ai-loading');
    const container = document.getElementById('ai-insights-container');

    btn.disabled = true;
    loading.classList.remove('d-none');
    container.classList.add('d-none');

    fetch("{{ route('api.ai.insights') }}")
        .then(res => res.json())
        .then(data => {
            loading.classList.add('d-none');
            btn.disabled = false;
            container.classList.remove('d-none');
            if (data.status === 'success') {
                container.innerHTML = data.insights;
            } else {
                container.innerHTML = `<div class="text-danger fw-bold">Error: ${data.message}</div>`;
            }
        })
        .catch(err => {
            loading.classList.add('d-none');
            btn.disabled = false;
            container.classList.remove('d-none');
            container.innerHTML = `<div class="text-danger fw-bold">Request failed: ${err.message}</div>`;
        });
});
</script>
@endsection