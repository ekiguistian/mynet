<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToBranch;

    protected $fillable = ['branch_id', 'sku', 'name', 'category', 'unit', 'stock_qty', 'minimum_stock', 'cost_price', 'sale_price', 'active'];

    protected function casts(): array
    {
        return ['stock_qty' => 'decimal:2', 'minimum_stock' => 'decimal:2', 'cost_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'active' => 'boolean'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }
}
