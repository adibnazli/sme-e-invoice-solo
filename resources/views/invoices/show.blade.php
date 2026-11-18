@extends('layouts.admin')

@section('content')
<style>
    /* Top Action & Mode Switcher Bar */
    .doc-control-bar {
        background: #FFFFFF;
        border-radius: 20px;
        padding: 0.9rem 1.35rem;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.025);
    }

    .mode-switch-group {
        background-color: #F1F5F9;
        padding: 0.3rem;
        border-radius: 14px;
        display: inline-flex;
        gap: 0.25rem;
    }

    .mode-btn {
        border: none;
        background: transparent;
        color: #64748B;
        font-size: 0.82rem;
        font-weight: 700;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        transition: all 0.15s ease;
    }

    .mode-btn.active {
        background: #FFFFFF;
        color: #1D4ED8;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }

    /* Main Printable Document Sheet */
    .invoice-sheet {
        background: #FFFFFF;
        border-radius: 24px;
        padding: 2.75rem;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.03);
    }

    .info-sub-box {
        background-color: #F8FAFC;
        border: 1px solid #EEF2F6;
        border-radius: 16px;
        padding: 1rem 1.25rem;
    }

    .dispatch-alert-box {
        background-color: #FEF2F2;
        border: 1px solid #FECACA;
        border-radius: 14px;
        padding: 0.85rem 1.15rem;
    }

    /* Soft Status Pills */
    .soft-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.73rem;
        font-weight: 700;
        padding: 0.35em 0.8em;
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

    .pill-lhdn {
        background-color: #E0F2FE;
        color: #0284C7;
    }

    .btn-telegram {
        background: linear-gradient(135deg, #0284C7 0%, #0369A1 100%);
        color: #FFFFFF;
        border: none;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.22);
    }

    .btn-telegram:hover {
        background: linear-gradient(135deg, #0369A1 0%, #075985 100%);
        color: #FFFFFF;
    }

    /* Print-Specific Overrides for Crisp A4 Output */
    @media print {
        body {
            background: #FFFFFF !important;
            padding: 0 !important;
        }
        .app-shell {
            background: #FFFFFF !important;
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
        }
        .invoice-sheet {
            box-shadow: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
        }
    }
</style>

<div class="container-fluid px-0" style="max-width: 1080px;">
    @if(session('success'))
        <div class="alert alert-success border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3 d-print-none">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-4 shadow-sm fw-semibold py-2 px-3 small mb-3 d-print-none">
            <i class="bi bi-exclamation-circle-fill me-1"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Top Action & Mode Toggle Bar -->
    <div class="doc-control-bar d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 d-print-none">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <span class="fw-bold small text-secondary me-1">Document Mode:</span>
            <div class="mode-switch-group" role="group">
                <button type="button" id="btn-mode-invoice" class="mode-btn active" onclick="setDocMode('INVOICE')">
                    <i class="bi bi-receipt me-1"></i> Tax Invoice / E-Invoice (Priced)
                </button>
                <button type="button" id="btn-mode-do" class="mode-btn" onclick="setDocMode('DO')">
                    <i class="bi bi-truck me-1"></i> Lorry Delivery Order - DO (Unpriced)
                </button>
            </div>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="{{ route('orders.index') }}" class="btn btn-light border text-secondary fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Orders
            </a>
            <a href="{{ route('pos') }}" class="btn btn-light border text-dark fw-semibold">
                Back to POS
            </a>
            <button type="button" class="btn btn-telegram fw-bold px-3" data-bs-toggle="modal" data-bs-target="#sendTelegramModal">
                <i class="bi bi-telegram me-1"></i> Send to Telegram
            </button>
            <button class="btn btn-primary fw-bold px-3" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Print Current View
            </button>
        </div>
    </div>

    <!-- Printable Invoice / DO Sheet -->
    <div class="invoice-sheet">
        <div class="row g-4 mb-4 pb-4 border-bottom align-items-start">
            <!-- Left: Store Info & Site Address -->
            <div class="col-sm-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary text-white" style="width: 36px; height: 36px;">
                        <i class="bi bi-grid-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-0">SME Hardware Hub</h4>
                </div>
                <div class="text-secondary small">Senawang Industrial Park, Negeri Sembilan</div>
                <div class="text-muted" style="font-size: 0.78rem;">LHDN TIN: C2584910200 &nbsp;•&nbsp; SST Reg: N/A</div>

                @if($order->delivery_type === 'SITE_DELIVERY')
                    <div class="dispatch-alert-box mt-3">
                        <div class="fw-bold text-uppercase text-danger" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                            <i class="bi bi-geo-alt-fill me-1"></i> Lorry Dispatch / Site Delivery To:
                        </div>
                        <div class="fw-bold text-dark small mt-1">{{ $order->delivery_address }}</div>
                    </div>
                @endif
            </div>

            <!-- Right: Document Title, Metadata & Customer Box -->
            <div class="col-sm-6 text-sm-end">
                <h4 class="fw-bold text-primary mb-2" id="doc-title">
                    {{ $order->invoice_type === 'LHDN_EINVOICE' ? 'LHDN VALIDATED E-INVOICE' : 'STANDARD RECEIPT / INVOICE' }}
                </h4>
                <div class="small mb-1"><span class="text-muted">Order / Ref No:</span> <strong class="text-dark">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong></div>
                <div class="small mb-1"><span class="text-muted">Date:</span> <strong class="text-dark">{{ $order->created_at->format('d M Y, h:i A') }}</strong></div>
                <div class="small mb-2"><span class="text-muted">Issued By:</span> <strong class="text-dark">{{ $order->user->name }}</strong></div>

                <div class="mt-2 price-col">
                    <span class="soft-pill {{ $order->payment_status === 'CREDIT_HUTANG' ? 'pill-hutang' : 'pill-paid' }}">
                        {{ $order->payment_status === 'CREDIT_HUTANG' ? 'CREDIT TERM (HUTANG)' : 'PAID (' . $order->payment_method . ')' }}
                    </span>
                    <span class="soft-pill pill-lhdn ms-1">{{ $order->lhdn_status }}</span>
                </div>

                @if($order->lhdn_uin)
                    <div class="text-muted mt-1 price-col" style="font-size: 0.76rem;">
                        <strong>LHDN UIN:</strong> {{ $order->lhdn_uin }}
                    </div>
                @endif

                @if($order->customer)
                    <div class="info-sub-box mt-3 text-start d-inline-block">
                        <div class="small">
                            <span class="text-muted d-block" style="font-size: 0.7rem;">CUSTOMER / CONTRACTOR</span>
                            <strong>{{ $order->customer->name }}</strong> <span class="text-muted">({{ $order->customer->phone }})</span>
                            @if($order->customer->telegram_chat_id)
                                <div class="text-info fw-semibold mt-1" style="font-size: 0.73rem;">
                                    <i class="bi bi-telegram me-1"></i>Telegram ID: {{ $order->customer->telegram_chat_id }}
                                </div>
                            @endif
                        </div>
                        @if($order->payment_status === 'CREDIT_HUTANG')
                            <div class="text-danger fw-bold mt-1 price-col" style="font-size: 0.78rem;">
                                Updated Account Debt: RM {{ number_format($order->customer->current_debt, 2) }}
                            </div>
                        @else
                            <div class="text-success fw-bold mt-1 price-col" style="font-size: 0.78rem;">
                                Loyalty Points Earned: +{{ floor($order->total_amount / 10) }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="table-responsive mb-4">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 7%;">#</th>
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
                        <td class="text-muted fw-semibold">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $item->product->name }}</div>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                SKU: {{ $item->product->sku }} &nbsp;•&nbsp; LHDN Class: {{ $item->product->lhdn_class_code ?? '022' }}
                            </small>
                        </td>
                        <td class="text-center fw-bold">
                            {{ $item->quantity }} {{ $item->unit_sold ?? $item->product->unit_type }}
                        </td>
                        <td class="text-end price-col">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end fw-bold price-col">{{ number_format($item->subtotal, 2) }}</td>
                        <td class="text-center do-col d-none fw-semibold text-secondary">[ &nbsp;&nbsp;&nbsp; ] Verified Good</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Invoice Totals Footer -->
        <div class="row align-items-center price-col pt-2">
            <div class="col-sm-7">
                @if($order->raw_ai_prompt)
                    <div class="info-sub-box small text-muted">
                        <strong class="text-dark d-block mb-1"><i class="bi bi-whatsapp text-success me-1"></i>Parsed from Contractor Message:</strong>
                        "{{ $order->raw_ai_prompt }}"
                    </div>
                @endif
            </div>
            <div class="col-sm-5">
                <div class="info-sub-box">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold">Payment Status</span>
                        <span class="fw-bold small">{{ $order->payment_status }} ({{ $order->payment_method }})</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <span class="fw-bold">Total Amount</span>
                        <span class="fs-4 fw-bold text-primary">RM {{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lorry Delivery Order (DO) Signature Footer -->
        <div class="do-col d-none mt-5 pt-4 border-top">
            <p class="small text-muted mb-4">
                <strong>DELIVERY ORDER TERMS:</strong> Please inspect all hardware materials (quantities, grades, and dimensions) upon lorry unloading. Claims for shortages or damaged goods ("barang tak cukup / rosak") will not be entertained once this Delivery Order is signed.
            </p>
            <div class="row text-center mt-5 pt-4">
                <div class="col-4">
                    <div class="border-top border-dark pt-2 mx-3 fw-bold small">Prepared By (Storekeeper)</div>
                    <small class="text-muted" style="font-size: 0.74rem;">Name & Date</small>
                </div>
                <div class="col-4">
                    <div class="border-top border-dark pt-2 mx-3 fw-bold small">Dispatched By (Lorry Driver)</div>
                    <small class="text-muted" style="font-size: 0.74rem;">Vehicle No & Signature</small>
                </div>
                <div class="col-4">
                    <div class="border-top border-dark pt-2 mx-3 fw-bold small">Received By (Site Supervisor)</div>
                    <small class="text-muted" style="font-size: 0.74rem;">Company Chop, IC & Signature</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Send / Resend E-Invoice to Customer Telegram -->
<div class="modal fade d-print-none" id="sendTelegramModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow" style="border-radius: 22px;">
            <form action="{{ route('invoice.telegram', $order->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4 text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                         style="width: 56px; height: 56px; background-color: #E0F2FE; color: #0284C7; font-size: 1.6rem;">
                        <i class="bi bi-telegram"></i>
                    </div>

                    <h6 class="fw-bold mb-1">Dispatch Telegram E-Invoice</h6>
                    <p class="text-muted small mb-3">
                        Send AI-formatted E-Invoice <strong>#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong> directly to
                        <strong>{{ $order->customer->name ?? 'Walk-in Customer' }}</strong>.
                    </p>

                    <div class="text-start mb-3">
                        <label class="form-label small fw-semibold">Recipient Telegram Chat ID</label>
                        <input type="text"
                               name="telegram_chat_id"
                               class="form-control"
                               value="{{ $order->customer->telegram_chat_id ?? env('TELEGRAM_CHAT_ID') }}"
                               placeholder="e.g. 123456789"
                               required>
                        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                            @if($order->customer)
                                Updating this ID will also save it to <strong>{{ $order->customer->name }}</strong>'s contractor profile for automatic future checkouts.
                            @else
                                Pre-filled with your default store Telegram Chat ID for walk-in demonstration.
                            @endif
                        </small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border flex-grow-1" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-telegram flex-grow-1 fw-bold">
                            <i class="bi bi-send-fill me-1"></i> Send Now
                        </button>
                    </div>
                </div>
            </form>
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
            title.className = 'fw-bold text-dark mb-2';
            btnDo.classList.add('active');
            btnInv.classList.remove('active');
        } else {
            priceCols.forEach(el => el.classList.remove('d-none'));
            doCols.forEach(el => el.classList.add('d-none'));
            title.innerText = '{{ $order->invoice_type === "LHDN_EINVOICE" ? "LHDN VALIDATED E-INVOICE" : "STANDARD RECEIPT / INVOICE" }}';
            title.className = 'fw-bold text-primary mb-2';
            btnInv.classList.add('active');
            btnDo.classList.remove('active');
        }
    }

    @if($order->delivery_type === 'SITE_DELIVERY')
        document.addEventListener('DOMContentLoaded', () => setDocMode('DO'));
    @endif
</script>
@endsection