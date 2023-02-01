<?php

namespace App\Models;

use Database\Factories\OrderProductFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\OrderProduct
 *
 * @property-read OrderDetail|null $detail
 * @property-read float $discount
 * @property-read float $origin_price
 * @property-read float $tax
 * @property-read float $total_price
 * @property-read ProductTranslation|null $translation
 * @method static OrderProductFactory factory(...$parameters)
 * @method static Builder|OrderProduct newModelQuery()
 * @method static Builder|OrderProduct newQuery()
 * @method static Builder|OrderProduct query()
 * @mixin Eloquent
 */
class OrderProduct extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function translation(): HasOne
    {
        return $this->hasOne(ProductTranslation::class, 'product_id', 'product_id');
    }

    public function detail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }


    public function getOriginPriceAttribute($value): float
    {
        if (auth('sanctum')->check() && request()->is('api/v1/dashboard/user/*')){
            return round($value * $this->detail->order->rate, 2);
        } else {
            return $value;
        }
    }

    public function getTaxAttribute($value): float
    {
        if (auth('sanctum')->check() && request()->is('api/v1/dashboard/user/*')){
            return round($value * $this->detail->order->rate, 2);
        } else {
            return $value;
        }
    }

    public function getDiscountAttribute($value): float
    {
        if (auth('sanctum')->check() && request()->is('api/v1/dashboard/user/*')){
            return round($value * $this->detail->order->rate, 2);
        } else {
            return $value;
        }
    }

    public function getTotalPriceAttribute($value): float
    {
        if (auth('sanctum')->check() && request()->is('api/v1/dashboard/user/*')){
            return round($value * $this->detail->order->rate, 2);
        } else {
            return $value;
        }
    }
}
