<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $invoices = Invoice::with('client')->latest()->take(10)->get();

        $totalInvoiced = Invoice::with('items')->get()
            ->sum(fn (Invoice $i) => $i->grand_total);

        $totalCollected = Invoice::with('payments')->get()
            ->sum(fn (Invoice $i) => $i->amount_paid);

        $outstanding = $totalInvoiced - $totalCollected;

        $countByStatus = Invoice::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('dashboard', compact(
            'invoices',
            'totalInvoiced',
            'totalCollected',
            'outstanding',
            'countByStatus'
        ));
    }
}
