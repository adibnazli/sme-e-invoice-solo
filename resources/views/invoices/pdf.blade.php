<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>E-Invoice #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #0F172A;
            margin: 0;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #E2E8F0;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: bold;
            color: #1D4ED8;
            margin: 0 0 4px 0;
        }
        .muted {
            color: #64748B;
            font-size: 11px;
        }
        .doc-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid {
            background-color: #DCFCE7;
            color: #15803D;
        }
        .badge-hutang {
            background-color: #FEE2E2;
            color: #DC2626;
        }
        .badge-lhdn {
            background-color: #E0F2FE;
            color: #0284C7;
        }
        .info-box {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #F1F5F9;
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
            padding: 10px 8px;
            text-align: left;
            border-bottom: 1px solid #CBD5E1;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #EEF2F6;
            font-size: 12px;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .total-box {
            width: 260px;
            float: right;
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 12px;
        }
        .footer {
            clear: both;
            margin-top: 50px;
            padding-top: 14px;
            border-top: 1px solid #E2E8F0;
            font-size: 10px;
            color: #94A3B8;
            text-align: center;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="brand-title">SME Hardware Hub</div>
                <div class="muted">Senawang Industrial Park, 70450 Seremban, Negeri Sembilan</div>
                <div class="muted">LHDN TIN: C2584910200 &nbsp;|&nbsp; Class Code: 022</div>

                @if($order->delivery_type === 'SITE_DELIVERY')
                    <div style="margin-top: 10px; padding: 8px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 6px;">
                        <strong style="color: #DC2626; font-size: 10px;">LORRY SITE DELIVERY TO:</strong><br>
                        <span style="font-weight: bold;">{{ $order->delivery_address }}</span>
                    </div>
                @endif
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div style="font-size: 16px; font-weight: bold; color: #1E3A8A; margin-bottom: 6px;">
                    {{ $order->invoice_type === 'LHDN_EINVOICE' ? 'LHDN VALIDATED E-INVOICE' : 'OFFICIAL E-INVOICE / RECEIPT' }}
                </div>
                <div><span class="muted">Invoice No:</span> <strong>#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong></div>
                <div><span class="muted">Date:</span> <strong>{{ $order->created_at->format('d M Y, h:i A') }}</strong></div>
                <div><span class="muted">Issued By:</span> <strong>{{ $order->user->name ?? 'Cashier' }}</strong></div>
                @if($order->lhdn_uin)
                    <div><span class="muted">LHDN UIN:</span> <strong>{{ $order->lhdn_uin }}</strong></div>
                @endif
                <div style="margin-top: 8px;">
                    <span class="doc-badge {{ $order->payment_status === 'CREDIT_HUTANG' ? 'badge-hutang' : 'badge-paid' }}">
                        {{ $order->payment_status === 'CREDIT_HUTANG' ? 'CREDIT (HUTANG)' : 'PAID (' .$order->payment_method . ')' }}
                    </span>
                    <span class="doc-badge badge-lhdn">{{ $order->lhdn_status }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="info-box">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60%;">
                    <div class="muted" style="font-size: 10px; text-transform: uppercase;">Billed To Customer / Contractor:</div>
                    <div style="font-size: 13px; font-weight: bold; margin-top: 2px;">
                        {{ $order->customer ? $order->customer->name : 'Walk-in Cash Customer' }}
                    </div>
                    @if($order->customer)
                        <div class="muted">Phone: {{ $order->customer->phone }} {{ $order->customer->email ? '\vert{} ' . $order->customer->email : '' }}</div>
                    @endif
                </td>
                <td style="width: 40%; text-align: right; vertical-align: middle;">
                    @if($order->customer &&$order->payment_status === 'CREDIT_HUTANG')
                        <div class="muted" style="font-size: 10px;">UPDATED HUTANG BALANCE</div>
                        <div style="font-size: 14px; font-weight: bold; color: #DC2626;">
                            RM {{ number_format($order->customer->current_debt, 2) }}
                        </div>
                    @elseif($order->customer)
                        <div class="muted" style="font-size: 10px;">LOYALTY POINTS EARNED</div>
                        <div style="font-size: 13px; font-weight: bold; color: #15803D;">
                            +{{ floor($order->total_amount / 10) }} pts
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 46%;">Hardware Item Description & SKU</th>
                <th class="text-center" style="width: 16%;">Qty & Unit</th>
                <th class="text-right" style="width: 16%;">Unit Price (RM)</th>
                <th class="text-right" style="width: 16%;">Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx =>$item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $item->product->name ?? 'Hardware Item' }}</strong><br>
                    <span class="muted">SKU: {{ $item->product->sku ?? '-' }} | LHDN Class: 022</span>
                </td>
                <td class="text-center"><strong>{{ $item->quantity }} {{ $item->unit_sold ?? ($item->product->unit_type ?? 'unit') }}</strong></td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($item->subtotal, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <table style="width: 100%;">
            <tr>
                <td class="muted">Fulfillment:</td>
                <td class="text-right"><strong>{{ $order->delivery_type }}</strong></td>
            </tr>
            <tr>
                <td class="muted" style="padding-top: 4px;">Payment Term:</td>
                <td class="text-right" style="padding-top: 4px;"><strong>{{ $order->payment_status }}</strong></td>
            </tr>
            <tr>
                <td style="padding-top: 10px; border-top: 1px solid #CBD5E1; font-weight: bold; font-size: 14px;">Grand Total:</td>
                <td class="text-right" style="padding-top: 10px; border-top: 1px solid #CBD5E1; font-weight: bold; font-size: 16px; color: #1D4ED8;">
                    RM {{ number_format($order->total_amount, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Computer-generated Official E-Invoice • SME Hardware Hub (Senawang Branch) • No signature required.
    </div>

</body>
</html>