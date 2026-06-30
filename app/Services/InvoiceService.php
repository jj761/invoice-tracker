<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    public function __construct(private PaymentService $paymentService) {}

    public function create(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $data['invoice_number'] = $this->nextInvoiceNumber();
            $invoice = Invoice::create($data);
            $invoice->items()->createMany($items);

            return $invoice->fresh('items');
        });
    }

    public function update(Invoice $invoice, array $data, ?array $items = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $invoice->update($data);

            if ($items !== null) {
                if ($invoice->payments()->exists()) {
                    throw new RuntimeException(
                        'Cannot edit line items on an invoice with recorded payments.'
                    );
                }
                $invoice->items()->delete();
                $invoice->items()->createMany($items);
            }

            // If editing pushed the due date into the future, an "overdue"
            // invoice no longer qualifies as overdue. Revert it based on
            // actual payment state. We never set status TO overdue here —
            // that remains the scheduled command's job, including the
            // overdue_logs audit entry.
            if (array_key_exists('due_date', $data)
                && $invoice->status === 'overdue'
                && $invoice->due_date->isFuture()
            ) {
                $this->paymentService->recalculateStatus($invoice);
            }

            return $invoice->fresh('items');
        });
    }

    private function nextInvoiceNumber(): string
    {
        $last = Invoice::lockForUpdate()
            ->orderByDesc('id')
            ->first();
        if (! $last) {
            return 'INV-0001';
        }
        $lastNumber = (int) substr($last->invoice_number, 4);
        $next = $lastNumber + 1;

        return 'INV-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
