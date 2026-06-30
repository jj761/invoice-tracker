<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(Request $request): View
    {
        $query = Invoice::with('client')
            ->orderBy(
                $request->get('sort', 'created_at'),
                $request->get('direction', 'desc')
            );

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        $clients = Client::orderBy('name')->get();

        return view('invoices.create', compact('clients'));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('items');
        $items = $request->validated()['items'];
        $invoice = $this->invoiceService->create($data, $items);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load('client', 'items', 'payments', 'overdueLogs');

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $clients = Client::orderBy('name')->get();
        $invoice->load('items');

        return view('invoices.edit', compact('invoice', 'clients'));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->safe()->except('items');
        $items = $request->validated()['items'];
        $this->invoiceService->update($invoice, $data, $items);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }
}
