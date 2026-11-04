@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="bi bi-people-fill me-2"></i> Customers & Loyalty</h2>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newCustomerModal">
        <i class="bi bi-plus-lg me-2"></i> Register Customer
    </button>
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
                        <th class="ps-4">Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Loyalty Points</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $customer->name }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-6">
                                <i class="bi bi-star-fill me-1"></i> {{ $customer->loyalty_points }} pts
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="newCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Register New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email (Optional)</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection