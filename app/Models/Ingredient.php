<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'current_stock',
        'minimum_stock',
        'purchase_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'purchase_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function recipeDetails(): HasMany
    {
        return $this->hasMany(RecipeDetail::class);
    }

    public function purchaseDetails(): HasMany
    {
        return $this->hasMany(PurchaseDetail::class);
    }
}
