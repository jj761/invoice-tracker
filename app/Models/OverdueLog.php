<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverdueLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'flagged_at',
    ];

    protected function casts(): array
    {
        return [
            'flagged_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
