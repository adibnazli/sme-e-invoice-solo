@extends('layouts.admin')

@section('content')
<div class="container mt-2">
    @if(session('success'))
        <div class="alert alert-success fw-bold">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <div class="row mb-4">
                <div class="col-sm-6">
                    <h3 class="fw-bold">SME Hardware Hub</h3>
                    <div>Senawang, Negeri Sembilan</div>
                </div>
                <div class="col-sm-6 text-end">
                    <h3 class="fw-bold text-primary">TAX INVOICE</h3>
                    <div><strong>Order ID:</strong> #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div><strong>Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}</div>
                    <div><strong>Cashier:</strong> {{ $order->user->name }}</div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item Description</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price (RM)</th>
                            <th class="text-end">Amount (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name }}</strong><br>
                                <small class="text-muted">{{ $item->product->sku }}</small>
                            </td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row">
                <div class="col-sm-8"></div>
                <div class="col-sm-4">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Total Due:</strong></td>
                            <td class="text-end fs-5 fw-bold text-success">RM {{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Method:</strong></td>
                            <td class="text-end fw-bold">{{ $order->payment_method }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="text-center mt-5 d-print-none">
                <button class="btn btn-primary fw-bold px-4" onclick="window.print()">
                    <i class="bi bi-printer-fill me-2"></i> Print Invoice
                </button>
                <a href="{{ route('pos') }}" class="btn btn-secondary fw-bold px-4 ms-2">
                    Back to POS
                </a>
            </div>
        </div>
    </div>
</div>
@endsection