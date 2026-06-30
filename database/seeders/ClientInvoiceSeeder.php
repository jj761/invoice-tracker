<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;

class ClientInvoiceSeeder extends Seeder
{
    public function __construct(
        private InvoiceService $invoiceService,
        private PaymentService $paymentService,
    ) {}

    public function run(): void
    {
        $clientsData = [
            ['name' => 'Acme Trading Co.', 'email' => 'billing@acmetrading.com', 'phone' => '17001234', 'company_name' => 'Acme Trading Co.', 'billing_address' => 'Building 12, Road 1702, Manama, Bahrain'],
            ['name' => 'Gulf Retail Group', 'email' => 'accounts@gulfretail.com', 'phone' => '17005678', 'company_name' => 'Gulf Retail Group', 'billing_address' => 'Office 304, Bahrain Bay, Manama, Bahrain'],
            ['name' => 'Pearl Hospitality LLC', 'email' => 'finance@pearlhospitality.com', 'phone' => '17009012', 'company_name' => 'Pearl Hospitality LLC', 'billing_address' => 'Suite 8, Seef District, Manama, Bahrain'],
        ];

        foreach ($clientsData as $clientData) {
            $client = Client::create($clientData);

            // Invoice 1: fully paid, past due date — must NOT get flagged overdue
            // (paid invoices are never touched by the daily scheduler command per Section 3.1).
            $paidInvoice = $this->invoiceService->create(
                [
                    'client_id' => $client->id,
                    'issue_date' => now()->subDays(30),
                    'due_date' => now()->subDays(15),
                    'tax_percent' => 10,
                    'notes' => 'Fully settled invoice.',
                ],
                [
                    ['description' => 'Consulting services', 'quantity' => 10, 'unit_price' => 50],
                    ['description' => 'Setup fee', 'quantity' => 1, 'unit_price' => 100],
                ]
            );
            $this->paymentService->record($paidInvoice, [
                'amount' => $paidInvoice->grand_total,
                'paid_on' => now()->subDays(10)->format('Y-m-d'),
            ]);

            // Invoice 2: partially paid, future due date.
            $partialInvoice = $this->invoiceService->create(
                [
                    'client_id' => $client->id,
                    'issue_date' => now()->subDays(5),
                    'due_date' => now()->addDays(10),
                    'tax_percent' => 5,
                    'notes' => 'Partial payment received, balance not yet due.',
                ],
                [
                    ['description' => 'Monthly subscription', 'quantity' => 1, 'unit_price' => 300],
                    ['description' => 'Support hours', 'quantity' => 4, 'unit_price' => 40],
                ]
            );
            $this->paymentService->record($partialInvoice, [
                'amount' => 150,
                'paid_on' => now()->subDays(2)->format('Y-m-d'),
            ]);

            // Invoice 3: unpaid, past due date — candidate for the daily overdue command.
            $this->invoiceService->create(
                [
                    'client_id' => $client->id,
                    'issue_date' => now()->subDays(20),
                    'due_date' => now()->subDays(5),
                    'tax_percent' => 0,
                    'notes' => 'No payment received, past due.',
                ],
                [
                    ['description' => 'One-time project fee', 'quantity' => 1, 'unit_price' => 500],
                ]
            );
        }
    }
}
