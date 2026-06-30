<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'tax_percent',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'tax_percent' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /**
     * Sum of (quantity * unit_price) across all line items.
     */
    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items->sum(fn (InvoiceItem $item) => $item->line_total),
        );
    }

    /**
     * Tax applied to the subtotal, using tax_percent.
     */
    protected function taxAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->subtotal * ($this->tax_percent / 100), 2),
        );
    }

    /**
     * subtotal + tax_amount.
     */
    protected function grandTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->subtotal + $this->tax_amount,
        );
    }

    /**
     * Sum of all recorded payments against this invoice.
     */
    protected function amountPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payments->sum('amount'),
        );
    }

    /**
     * grand_total - amount_paid.
     */
    protected function amountDue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->grand_total - $this->amount_paid,
        );
    }

    public function overdueLogs(): HasMany
    {
        return $this->hasMany(OverdueLog::class);
    }
}
