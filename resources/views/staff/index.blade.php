@extends('layouts.admin')

@section('content')
<style>
    .staff-avatar {
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

    .pill-owner {
        background-color: #DBEAFE;
        color: #1D4ED8;
    }

    .pill-cashier {
        background-color: #DCFCE7;
        color: #15803D;
    }
</style>

<!-- Header Row -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Staff & Cashier Accounts</h4>
        <small class="text-muted">Manage POS terminal logins and role permissions</small>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newStaffModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add Cashier / Staff
    </button>
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

<!-- Staff Table Card -->
<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">Staff Name</th>
                    <th>Login Username</th>
                    <th>System Role</th>
                    <th>Created Date</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staff as $member)
                <tr>
                    <td class="ps-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="staff-avatar">
                                {{ strtoupper(substr($member->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $member->name }}</div>
                                @if($member->id === Auth::id())
                                    <small class="text-primary fw-semibold" style="font-size: 0.72rem;">Current Session</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        <code class="text-dark fw-bold bg-light px-2 py-1 rounded border">{{ $member->username }}</code>
                    </td>
                    <td>
                        <span class="soft-pill {{ $member->role === 'OWNER' ? 'pill-owner' : 'pill-cashier' }}">
                            <i class="bi {{ $member->role === 'OWNER' ? 'bi-shield-lock-fill' : 'bi-person-badge-fill' }}"></i>
                            {{ $member->role }}
                        </span>
                    </td>
                    <td class="text-muted small">
                        {{ $member->created_at ? $member->created_at->format('d M Y') : 'N/A' }}
                    </td>
                    <td class="text-end pe-3">
                        @if($member->id !== Auth::id())
                            <form action="{{ route('staff.destroy', $member->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove staff account {{ $member->username }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Register Cashier / Staff -->
<div class="modal fade" id="newStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <form action="{{ route('staff.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold">Register Cashier / Staff Account</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Ahmad (Cashier)" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Login Username</label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. cashier2" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Role Permission</label>
                            <select name="role" class="form-select" required>
                                <option value="CASHIER">CASHIER (POS & Sales)</option>
                                <option value="OWNER">OWNER (Full Access)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Initial Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection