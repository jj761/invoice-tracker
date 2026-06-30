<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        /* DomPDF has weak flexbox/grid support — table/float layout only, per project convention. */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
            padding-bottom: 20px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
        }

        .invoice-meta {
            text-align: right;
        }

        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 4px;
        }

        .client-table td {
            padding-bottom: 20px;
        }

        .items-table {
            margin-top: 10px;
            border: 1px solid #ddd;
        }

        .items-table th {
            background-color: #f5f5f5;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
        }

        .text-right {
            text-align: right;
        }

        .totals-table {
            width: 250px;
            float: right;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 4px 8px;
        }

        .totals-table .grand-total td {
            font-weight: bold;
            border-top: 1px solid #333;
        }

        .clear {
            clear: both;
        }

        .notes-section {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .footer-note {
            margin-top: 30px;
            font-size: 10px;
            color: #888;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border: 1px solid #999;
            font-size: 10px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <div class="company-name">Webtree Software Solutions W.L.L</div>
            </td>
            <td style="width: 50%;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-meta">
                    {{ $invoice->invoice_number }}<br>
                    <span class="status-badge">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="client-table">
        <tr>
            <td style="width: 50%;">
                <div class="section-title">Billed To</div>
                <strong>{{ $invoice->client->name }}</strong><br>
                {{ $invoice->client->email }}<br>
                @if ($invoice->client->company_name)
                    {{ $invoice->client->company_name }}<br>
                @endif
                @if ($invoice->client->billing_address)
                    {{ $invoice->client->billing_address }}
                @endif
            </td>
            <td style="width: 50%;">
                <div class="section-title">Invoice Details</div>
                <table>
                    <tr>
                        <td>Issue Date</td>
                        <td class="text-right">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <td>Due Date</td>
                        <td class="text-right">{{ $invoice->due_date->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <td>Tax %</td>
                        <td class="text-right">{{ number_format($invoice->tax_percent, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-right" style="width: 15%;">Quantity</th>
                <th class="text-right" style="width: 17%;">Unit Price</th>
                <th class="text-right" style="width: 18%;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Grand Total</td>
            <td class="text-right">{{ number_format($invoice->grand_total, 2) }}</td>
        </tr>
        <tr>
            <td>Amount Paid</td>
            <td class="text-right">{{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Amount Due</td>
            <td class="text-right">{{ number_format($invoice->amount_due, 2) }}</td>
        </tr>
    </table>

    <div class="clear"></div>

    @if ($invoice->notes)
        <div class="notes-section">
            <div class="section-title">Notes</div>
            {{ $invoice->notes }}
        </div>
    @endif

    <div class="footer-note">
        Payment Terms: Payment due by {{ $invoice->due_date->format('Y-m-d') }}. Please reference invoice number
        {{ $invoice->invoice_number }} with your payment.
    </div>

</body>

</html>
