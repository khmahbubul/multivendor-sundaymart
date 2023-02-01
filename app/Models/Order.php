<?php

namespace App\Models;

use App\Traits\Notification;
use App\Traits\Payable;
use App\Traits\Reviewable;
use Database\Factories\OrderFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $user_id
 * @property float $price
 * @property int $currency_id
 * @property int $rate
 * @property string|null $note
 * @property int $shop_id
 * @property float $tax
 * @property float|null $commission_fee
 * @property string $status
 * @property int|null $delivery_address_id
 * @property int|null $delivery_type_id
 * @property float $delivery_fee
 * @property int|null $deliveryman
 * @property string|null $delivery_date
 * @property string|null $delivery_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property int|null $total_discount
 * @property-read OrderCoupon|null $coupon
 * @property-read Currency|null $currency
 * @property-read UserAddress|null $deliveryAddress
 * @property-read User|null $deliveryMan
 * @property-read Delivery|null $deliveryType
 * @property-read Collection|OrderDetail[] $orderDetails
 * @property-read int|null $order_details_count
 * @property-read Review|null $review
 * @property-read Collection|Review[] $reviews
 * @property-read int|null $reviews_count
 * @property-read Shop $shop
 * @property-read Transaction|null $transaction
 * @property-read Collection|Transaction[] $transactions
 * @property-read int|null $transactions_count
 * @property-read User $user
 * @method static OrderFactory factory(...$parameters)
 * @method static Builder|Order filter($array)
 * @method static Builder|Order newModelQuery()
 * @method static Builder|Order newQuery()
 * @method static Builder|Order query()
 * @method static Builder|Order updatedDate($updatedDate)
 * @method static Builder|Order whereCommissionFee($value)
 * @method static Builder|Order whereCreatedAt($value)
 * @method static Builder|Order whereCurrencyId($value)
 * @method static Builder|Order whereDeletedAt($value)
 * @method static Builder|Order whereDeliveryAddressId($value)
 * @method static Builder|Order whereDeliveryDate($value)
 * @method static Builder|Order whereDeliveryFee($value)
 * @method static Builder|Order whereDeliveryTime($value)
 * @method static Builder|Order whereDeliveryTypeId($value)
 * @method static Builder|Order whereDeliveryman($value)
 * @method static Builder|Order whereId($value)
 * @method static Builder|Order whereNote($value)
 * @method static Builder|Order wherePrice($value)
 * @method static Builder|Order whereRate($value)
 * @method static Builder|Order whereShopId($value)
 * @method static Builder|Order whereStatus($value)
 * @method static Builder|Order whereTax($value)
 * @method static Builder|Order whereTotalDiscount($value)
 * @method static Builder|Order whereUpdatedAt($value)
 * @method static Builder|Order whereUserId($value)
 * @mixin Eloquent
 */
class Order extends Model
{
    use HasFactory, Payable, Notification, Reviewable;
    protected $guarded = [];
    protected $fillable = [
        'user_id','price','currency_id','rate','note','shop_id','tax','commission_fee','status','delivery_address_id','user_addresses',
        'delivery_type_id','delivery_fee','deliveryman','delivery_date','delivery_time','total_discount','bonus_shop_id'
    ];

    const STATUS = [
        'new',
        'accepted',
        'ready',
        'on_a_way',
        'delivered',
        'canceled',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'payable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): MorphOne
    {
        return $this->morphOne(Review::class, 'reviewable');
    }

    public function getPriceAttribute($value): float
    {
        if (request()->is('api/v1/dashboard/user/*')){
            return round($value * $this->rate, 2);
        } else {
            return $value;
        }
    }

    public function deliveryMan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deliveryman');
    }

    public function deliveryType(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'delivery_type_id');
    }

    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'delivery_address_id');
    }

    public function coupon(): HasOne
    {
        return $this->hasOne(OrderCoupon::class, 'order_id');
    }

        public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function bonusShop(): HasOne
    {
        return $this->hasOne(BonusShop::class, 'id','bonus_shop_id');
    }


    public function scopeUpdatedDate($query, $updatedDate)
    {
        return $query->where('updated_at', '>', $updatedDate);
    }

    public function scopeFilter($query, $array)
    {
        $query->when(isset($array['status']), function ($q) use ($array) {
            $q->where('status', $array['status']);
        })
            ->when(isset($array['user_id']), function ($q) use ($array) {
                $q->where('user_id', $array['user_id']);
            })
            ->when(isset($array['order_id']), function ($q) use ($array) {
                $q->where('id', $array['order_id']);
            });
    }
}
