<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function record(Invoice $invoice, array $data): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $invoice->load('items', 'payments');
            $amountDue = bcsub(
                (string) $invoice->grand_total,
                (string) $invoice->amount_paid,
                2
            );

            if (bccomp((string) $data['amount'], $amountDue, 2) > 0) {
                throw new RuntimeException(
                    "Payment amount ({$data['amount']}) exceeds the invoice's amount due ({$amountDue})."
                );
            }

            $payment = $invoice->payments()->create($data);

            $this->recalculateStatus($invoice);

            return $payment;
        });
    }

    public function recalculateStatus(Invoice $invoice): void
    {
        $invoice->load('items', 'payments');

        $grandTotal = (string) $invoice->grand_total;
        $amountPaid = (string) $invoice->amount_paid;
        $comparison = bccomp($amountPaid, $grandTotal, 2);

        if ($comparison >= 0) {
            $status = 'paid';
        } elseif (bccomp($amountPaid, '0.00', 2) > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'unpaid';
        }

        $invoice->update(['status' => $status]);
    }
}
