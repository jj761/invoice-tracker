<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    /**
     * Create an invoice with its line items, transactionally.
     */
    public function create(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $data['invoice_number'] = $this->nextInvoiceNumber();

            $invoice = Invoice::create($data);
            $invoice->items()->createMany($items);

            return $invoice->fresh('items');
        });
    }

    /**
     * Update an invoice's fields and (optionally) its line items.
     * line items cannot be edited once any payment has been recorded
     * against the invoice, regardless of status.
     */
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
