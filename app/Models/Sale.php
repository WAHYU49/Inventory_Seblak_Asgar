<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'sale_number',
        'idempotency_key',
        'sale_date',
        'total_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleDetail::class);
    }
}
