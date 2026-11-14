@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff me-2"></i> Order Ledger, E-Invoices & Lorry DO Dispatch</h2>
    <a href="{{ route('pos') }}" class="btn btn-primary fw-bold">
        <i class="bi bi-cart-plus-fill me-1"></i> New POS Order
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Order ID & Date</th>
                        <th>Customer / Contractor</th>
                        <th>Payment Term</th>
                        <th>Fulfillment Mode</th>
                        <th>LHDN Status</th>
                        <th class="text-end">Total (RM)</th>
                        <th class="text-end pe-4">Print / View</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="ps-4">
                            <strong>#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong><br>
                            <small class="text-muted">{{ $order->created_at->format('d M Y, h:i A') }} | Staff: {{ $order->user->name ?? 'N/A' }}</small>
                        </td>
                        <td>
                            @if($order->customer)
                                <strong>{{ $order->customer->name }}</strong><br>
                                <span class="badge bg-light text-dark border">{{ $order->customer->customer_type }}</span>
                            @else
                                <span class="text-muted">Walk-in Customer</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $order->payment_status === 'CREDIT_HUTANG' ? 'bg-danger' : 'bg-success' }}">
                                {{ $order->payment_status === 'CREDIT_HUTANG' ? 'HUTANG (Credit)' : 'PAID (' . $order->payment_method . ')' }}
                            </span>
                        </td>
                        <td>
                            @if($order->delivery_type === 'SITE_DELIVERY')
                                <span class="badge bg-warning text-dark"><i class="bi bi-truck"></i> SITE DELIVERY</span><br>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 180px;">{{ $order->delivery_address }}</small>
                            @else
                                <span class="badge bg-secondary">COUNTER PICKUP</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ in_array($order->lhdn_status, ['VALIDATED', 'CONSOLIDATED']) ? 'bg-info text-dark' : 'bg-secondary' }}">
                                {{ $order->lhdn_status }}
                            </span>
                            @if($order->lhdn_uin)
                                <br><small class="text-muted">{{ $order->lhdn_uin }}</small>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-primary">
                            RM {{ number_format($order->total_amount, 2) }}
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('invoice.show', $order->id) }}" class="btn btn-sm btn-outline-dark fw-bold">
                                <i class="bi bi-printer me-1"></i> Invoice / Lorry DO
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted fw-bold">No orders recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection