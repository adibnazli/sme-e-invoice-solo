@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="bi bi-people-fill me-2"></i> Contractors, Credit Limits & Loyalty</h2>
    @if(Auth::user()->role === 'OWNER')
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
        <i class="bi bi-plus-lg me-2"></i> Register Customer / Contractor
    </button>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success fw-bold">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger fw-bold">{{ $errors->first() }}</div>
@endif

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Customer / Company Name</th>
                        <th>Account Type</th>
                        <th>Hutang (Current Debt)</th>
                        <th>Credit Limit</th>
                        <th>Loyalty Points</th>
                        <th class="text-end pe-4">Credit Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
                        <td class="ps-4">
                            <strong>{{ $customer->name }}</strong><br>
                            <small class="text-muted">{{ $customer->phone }} {{ $customer->email ? '| ' . $customer->email : '' }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $customer->customer_type === 'CONTRACTOR' ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $customer->customer_type }}
                            </span>
                        </td>
                        <td>
                            @if($customer->current_debt > 0)
                                <span class="fw-bold text-danger">RM {{ number_format($customer->current_debt, 2) }}</span>
                            @else
                                <span class="text-muted">RM 0.00</span>
                            @endif
                        </td>
                        <td>
                            @if($customer->customer_type === 'CONTRACTOR')
                                <span class="fw-bold">RM {{ number_format($customer->credit_limit, 2) }}</span>
                                <br>
                                <small class="text-success">Available: RM {{ number_format(max(0, $customer->credit_limit - $customer->current_debt), 2) }}</small>
                            @else
                                <span class="text-muted">Cash Only</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                <i class="bi bi-star-fill me-1"></i> {{ $customer->loyalty_points }} pts
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            @if($customer->current_debt > 0 && Auth::user()->role === 'OWNER')
                                <button class="btn btn-sm btn-success fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#repay-{{ $customer->id }}">
                                    <i class="bi bi-cash-coin me-1"></i> Settle Hutang
                                </button>
                            @elseif($customer->current_debt > 0)
                                <span class="badge bg-danger">Owner Approval Req.</span>
                            @else
                                <span class="badge bg-light text-muted border">Clear</span>
                            @endif
                        </td>
                    </tr>
                    @if($customer->current_debt > 0 && Auth::user()->role === 'OWNER')
                    <tr class="collapse bg-light" id="repay-{{ $customer->id }}">
                        <td colspan="6" class="p-3 ps-4">
                            <form action="{{ route('customers.repay', $customer->id) }}" method="POST" class="row g-2 align-items-center">
                                @csrf
                                <div class="col-auto">
                                    <span class="fw-bold text-success">Record Debt Repayment for {{ $customer->name }}:</span>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">Amount Paid (RM)</span>
                                        <input type="number" step="0.01" name="repayment_amount" class="form-control" max="{{ $customer->current_debt }}" value="{{ $customer->current_debt }}" required>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary fw-bold">Confirm Payment</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if(Auth::user()->role === 'OWNER')
<div class="modal fade" id="newCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Register Customer / Contractor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name / Construction Company</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Binaan Maju Jaya Enterprise" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Email (Optional)</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Account Tier</label>
                            <select name="customer_type" class="form-select" required>
                                <option value="CONTRACTOR">CONTRACTOR (Credit Allowed)</option>
                                <option value="WALK_IN">WALK-IN (Cash Only)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Credit Limit (RM)</label>
                            <input type="number" step="0.01" name="credit_limit" class="form-control" value="5000.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection