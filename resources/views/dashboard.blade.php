@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2"></i> SME Owner Financial & LHDN Command Center</h2>
    <a href="{{ route('dashboard.export.csv') }}" class="btn btn-dark fw-bold">
        <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export Accounting CSV Ledger
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success fw-bold">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger fw-bold">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm border-0 h-100">
            <div class="card-body p-3">
                <h6 class="text-uppercase small fw-bold mb-1">Gross Revenue (All-Time)</h6>
                <h3 class="fw-bold mb-1">RM {{ number_format($totalRevenue, 2) }}</h3>
                <small class="opacity-75">Today: RM {{ number_format($todayRevenue, 2) }} ({{ $todayOrders }} orders)</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-secondary text-white shadow-sm border-0 h-100">
            <div class="card-body p-3">
                <h6 class="text-uppercase small fw-bold mb-1">Cost of Goods + Expenses</h6>
                <h3 class="fw-bold mb-1">RM {{ number_format($totalCogs + $totalExpenses, 2) }}</h3>
                <small class="opacity-75">COGS: RM {{ number_format($totalCogs, 2) }} | Exp: RM {{ number_format($totalExpenses, 2) }}</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card {{ $netProfit >= 0 ? 'bg-success' : 'bg-danger' }} text-white shadow-sm border-0 h-100">
            <div class="card-body p-3">
                <h6 class="text-uppercase small fw-bold mb-1">True Net Profit</h6>
                <h3 class="fw-bold mb-1">RM {{ number_format($netProfit, 2) }}</h3>
                <small class="opacity-75">Gross Margin: RM {{ number_format($grossProfit, 2) }}</small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-dark shadow-sm border-0 h-100">
            <div class="card-body p-3">
                <h6 class="text-uppercase small fw-bold mb-1">Uncollected Hutang (Credit)</h6>
                <h3 class="fw-bold mb-1">RM {{ number_format($totalHutang, 2) }}</h3>
                <small class="fw-bold">{{ $debtorsCount }} Active Contractor Accounts</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-dark text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bank2 me-2"></i> LHDN MyInvois Consolidation Hub</span>
                <span class="badge bg-info text-dark">Class Code: 022</span>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <p class="small text-muted mb-3">
                        Walk-in cash receipts not claimed as individual B2B E-Invoices must be batch-submitted to LHDN MyInvois before month-end closing.
                    </p>
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold">Pending Walk-In Receipts:</span>
                            <span class="badge bg-warning text-dark fs-6">{{ $pendingLhdnCount }} Receipts</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fw-bold">Pending Consolidation Value:</span>
                            <span class="fw-bold text-primary">RM {{ number_format($pendingLhdnAmount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Total Validated / Consolidated Orders:</span>
                            <span class="badge bg-success">{{ $validatedLhdnCount }} Orders</span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('dashboard.lhdn.consolidate') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2" {{ $pendingLhdnCount === 0 ? 'disabled' : '' }}>
                        <i class="bi bi-cloud-upload-fill me-1"></i> Consolidate & Submit {{ $pendingLhdnCount }} Receipts to LHDN
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-secondary text-white fw-bold py-3">
                <i class="bi bi-fuel-pump-fill me-2"></i> Shop Operating Expenses (Lorry Fuel, Wages, Rent)
            </div>
            <div class="card-body">
                <form action="{{ route('dashboard.expenses.store') }}" method="POST" class="row g-2 mb-3 pb-3 border-bottom">
                    @csrf
                    <div class="col-md-3">
                        <select name="category" class="form-select form-select-sm" required>
                            <option value="TRANSPORT_FUEL">Lorry Diesel / Fuel</option>
                            <option value="WAGES">Staff / Driver Wages</option>
                            <option value="RENT">Shop Lot Rent</option>
                            <option value="UTILITIES">TNB / Water / Internet</option>
                            <option value="MAINTENANCE">Lorry / Forklift Repair</option>
                            <option value="OTHER">Other Operating Cost</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="RM" required>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="expense_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <input type="text" name="description" class="form-control" placeholder="e.g. Lorry Nissan diesel site trip" required>
                            <button type="submit" class="btn btn-dark fw-bold">Add</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive" style="max-height: 170px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentExpenses as $exp)
                            <tr>
                                <td class="small">{{ \Carbon\Carbon::parse($exp->expense_date)->format('d M Y') }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $exp->category }}</span></td>
                                <td class="small">{{ $exp->description }}</td>
                                <td class="text-end fw-bold text-danger">- RM {{ number_format($exp->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small py-3">No operating expenses logged yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-robot fs-5 text-white"></i>
                    </div>
                    <h5 class="mb-0 fw-bold">Groq AI Predictive Insights</h5>
                </div>
                
                <div id="ai-insight-container" style="min-height: 80px;">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span class="text-muted">Analyzing Aiven database, historical sales, and seasonal trends...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-danger text-white fw-bold py-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> Low Stock Alerts
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Product Name</th>
                        <th>SKU</th>
                        <th>Threshold</th>
                        <th>Current Stock</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lowStockProducts as $product)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $product->name }}</td>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->low_stock_threshold }} {{ $product->unit_type }}</td>
                        <td>
                            <span class="badge bg-danger rounded-pill px-3 py-2">
                                {{ $product->stock_quantity }} {{ $product->unit_type }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary fw-bold">GRN Restock</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted fw-bold">
                            <i class="bi bi-check-circle-fill text-success me-2"></i> All hardware stock levels are optimal.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch('{{ route('api.ai.insights') }}')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('ai-insight-container');
            if(data.status === 'success') {
                container.innerHTML = data.insights;
            } else {
                container.innerHTML = `<p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i> ${data.message}</p>`;
            }
        })
        .catch(error => {
            document.getElementById('ai-insight-container').innerHTML = '<p class="text-danger">Failed to load AI insights.</p>';
        });
});
</script>
@endsection