<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\OverdueLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FlagOverdueInvoices extends Command
{
    protected $signature = 'invoices:flag-overdue';

    protected $description = 'Flag unpaid and partially paid invoices past their due date as overdue';

    public function handle(): int
    {
        $invoices = Invoice::whereIn('status', ['unpaid', 'partially_paid'])
            ->whereDate('due_date', '<', today())
            ->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices to flag.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($invoices) {
            foreach ($invoices as $invoice) {
                $invoice->update(['status' => 'overdue']);
                OverdueLog::create([
                    'invoice_id' => $invoice->id,
                    'flagged_at' => now(),
                ]);
            }
        });

        $this->info("Flagged {$invoices->count()} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
