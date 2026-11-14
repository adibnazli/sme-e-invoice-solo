@extends('layouts.admin')

@section('content')
<div class="container mt-2">
    @if(session('success'))
        <div class="alert alert-success fw-bold d-print-none">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none bg-white p-3 rounded shadow-sm border">
        <div>
            <span class="fw-bold me-2">Document Print Mode:</span>
            <div class="btn-group" role="group">
                <button type="button" id="btn-mode-invoice" class="btn btn-primary fw-bold" onclick="setDocMode('INVOICE')">
                    <i class="bi bi-receipt me-1"></i> Tax Invoice / E-Invoice (With Prices)
                </button>
                <button type="button" id="btn-mode-do" class="btn btn-outline-dark fw-bold" onclick="setDocMode('DO')">
                    <i class="bi bi-truck me-1"></i> Lorry Delivery Order - DO (Unpriced)
                </button>
            </div>
        </div>
        <div>
            <button class="btn btn-success fw-bold px-4" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Print Current View
            </button>
            <a href="{{ route('pos') }}" class="btn btn-secondary fw-bold ms-1">
                Back to POS
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-5">
            <div class="row mb-4 border-bottom pb-3">
                <div class="col-sm-6">
                    <h3 class="fw-bold mb-0">SME Hardware Hub</h3>
                    <div class="text-muted">Senawang Industrial Park, Negeri Sembilan</div>
                    <div class="small text-muted">LHDN TIN: C2584910200 | SST Reg: N/A</div>

                    @if($order->delivery_type === 'SITE_DELIVERY')
                        <div class="mt-3 p-2 border border-dark rounded bg-light">
                            <div class="fw-bold text-uppercase small text-danger"><i class="bi bi-geo-alt-fill"></i> Lorry Dispatch / Site Delivery To:</div>
                            <div class="fw-bold">{{ $order->delivery_address }}</div>
                        </div>
                    @endif
                </div>

                <div class="col-sm-6 text-end">
                    <h3 class="fw-bold text-primary" id="doc-title">
                        {{ $order->invoice_type === 'LHDN_EINVOICE' ? 'LHDN VALIDATED E-INVOICE' : 'STANDARD RECEIPT / INVOICE' }}
                    </h3>
                    <div><strong>Order / Ref No:</strong> #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div><strong>Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}</div>
                    <div><strong>Issued By:</strong> {{ $order->user->name }}</div>

                    <div class="mt-1 price-col">
                        <span class="badge {{ $order->payment_status === 'CREDIT_HUTANG' ? 'bg-danger' : 'bg-success' }}">
                            {{ $order->payment_status === 'CREDIT_HUTANG' ? 'CREDIT TERM (HUTANG)' : 'PAID (' . $order->payment_method . ')' }}
                        </span>
                        <span class="badge bg-secondary">{{ $order->lhdn_status }}</span>
                    </div>

                    @if($order->lhdn_uin)
                        <div class="small text-muted mt-1 price-col"><strong>LHDN UIN:</strong> {{ $order->lhdn_uin }}</div>
                    @endif

                    @if($order->customer)
                        <div class="mt-3 p-2 bg-light border rounded text-start d-inline-block">
                            <div><strong>Customer / Contractor:</strong> {{ $order->customer->name }} ({{ $order->customer->phone }})</div>
                            @if($order->payment_status === 'CREDIT_HUTANG')
                                <div class="text-danger fw-bold small price-col">Updated Account Debt: RM {{ number_format($order->customer->current_debt, 2) }}</div>
                            @else
                                <div class="text-success fw-bold small price-col">Loyalty Points Earned: +{{ floor($order->total_amount / 10) }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">#</th>
                            <th>Hardware Item Description & SKU</th>
                            <th class="text-center" style="width: 18%;">Qty & Unit</th>
                            <th class="text-end price-col" style="width: 18%;">Unit Price (RM)</th>
                            <th class="text-end price-col" style="width: 18%;">Subtotal (RM)</th>
                            <th class="text-center do-col d-none" style="width: 25%;">Site Check (Tick)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>
                                <strong>{{ $item->product->name }}</strong><br>
                                <small class="text-muted">SKU: {{ $item->product->sku }} | LHDN Class: {{ $item->product->lhdn_class_code ?? '022' }}</small>
                            </td>
                            <td class="text-center fw-bold fs-6">
                                {{ $item->quantity }} {{ $item->unit_sold ?? $item->product->unit_type }}
                            </td>
                            <td class="text-end price-col">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-bold price-col">{{ number_format($item->subtotal, 2) }}</td>
                            <td class="text-center do-col d-none">[ &nbsp;&nbsp;&nbsp; ] Verified Good</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row price-col">
                <div class="col-sm-7">
                    @if($order->raw_ai_prompt)
                        <div class="p-2 bg-light border rounded small text-muted">
                            <strong>Parsed from Contractor Message:</strong> "{{ $order->raw_ai_prompt }}"
                        </div>
                    @endif
                </div>
                <div class="col-sm-5">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-end fs-4 fw-bold text-primary">RM {{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Status:</strong></td>
                            <td class="text-end fw-bold">{{ $order->payment_status }} ({{ $order->payment_method }})</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="do-col d-none mt-5 pt-4 border-top">
                <p class="small text-muted mb-4">
                    <strong>DELIVERY ORDER TERMS:</strong> Please inspect all hardware materials (quantities, grades, and dimensions) upon lorry unloading. Claims for shortages or damaged goods ("barang tak cukup / rosak") will not be entertained once this Delivery Order is signed.
                </p>
                <div class="row text-center mt-5 pt-4">
                    <div class="col-4">
                        <div class="border-top border-dark pt-2 mx-3 fw-bold">Prepared By (Storekeeper)</div>
                        <small class="text-muted">Name & Date</small>
                    </div>
                    <div class="col-4">
                        <div class="border-top border-dark pt-2 mx-3 fw-bold">Dispatched By (Lorry Driver)</div>
                        <small class="text-muted">Vehicle No & Signature</small>
                    </div>
                    <div class="col-4">
                        <div class="border-top border-dark pt-2 mx-3 fw-bold">Received By (Site Supervisor)</div>
                        <small class="text-muted">Company Chop, IC & Signature</small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function setDocMode(mode) {
        const priceCols = document.querySelectorAll('.price-col');
        const doCols = document.querySelectorAll('.do-col');
        const title = document.getElementById('doc-title');
        const btnInv = document.getElementById('btn-mode-invoice');
        const btnDo = document.getElementById('btn-mode-do');

        if (mode === 'DO') {
            priceCols.forEach(el => el.classList.add('d-none'));
            doCols.forEach(el => el.classList.remove('d-none'));
            title.innerText = 'DELIVERY ORDER (DO)';
            title.className = 'fw-bold text-dark';
            btnDo.className = 'btn btn-dark fw-bold';
            btnInv.className = 'btn btn-outline-primary fw-bold';
        } else {
            priceCols.forEach(el => el.classList.remove('d-none'));
            doCols.forEach(el => el.classList.add('d-none'));
            title.innerText = '{{ $order->invoice_type === "LHDN_EINVOICE" ? "LHDN VALIDATED E-INVOICE" : "STANDARD RECEIPT / INVOICE" }}';
            title.className = 'fw-bold text-primary';
            btnInv.className = 'btn btn-primary fw-bold';
            btnDo.className = 'btn btn-outline-dark fw-bold';
        }
    }

    @if($order->delivery_type === 'SITE_DELIVERY')
        document.addEventListener('DOMContentLoaded', () => setDocMode('DO'));
    @endif
</script>
@endsection