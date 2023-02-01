<?php

namespace App\Models;

use App\Traits\Loadable;
use App\Traits\SetCurrency;
use Database\Factories\ShopFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Shop
 *
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property float $tax
 * @property int|null $delivery_range
 * @property float $percentage
 * @property array|null $location
 * @property string|null $phone
 * @property int|null $show_type
 * @property int $open
 * @property int $visibility
 * @property Carbon $open_time
 * @property Carbon $close_time
 * @property string|null $background_img
 * @property string|null $logo_img
 * @property float $min_amount
 * @property string $status
 * @property string|null $status_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $group_id
 * @property-read Collection|Brand[] $brands
 * @property-read int|null $brands_count
 * @property-read Collection|Category[] $categories
 * @property-read int|null $categories_count
 * @property-read Collection|Delivery[] $deliveries
 * @property-read int|null $deliveries_count
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read mixed $working_status
 * @property-read Group|null $group
 * @property-read Collection|Invitation[] $invitations
 * @property-read int|null $invitations_count
 * @property-read Collection|Order[] $orders
 * @property-read int|null $orders_count
 * @property-read Collection|Product[] $products
 * @property-read int|null $products_count
 * @property-read Collection|Review[] $reviews
 * @property-read int|null $reviews_count
 * @property-read User $seller
 * @property-read Collection|ShopPayment[] $shopPayments
 * @property-read int|null $shop_payments_count
 * @property-read ShopSubscription|null $subscription
 * @property-read ShopTranslation|null $translation
 * @property-read Collection|ShopTranslation[] $translations
 * @property-read int|null $translations_count
 * @property-read Collection|User[] $users
 * @property-read int|null $users_count
 * @property mixed $reviews_avg_rating
 * @method static ShopFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop filter($array)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Shop newQuery()
 * @method static Builder|Shop onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Shop query()
 * @method static \Illuminate\Database\Eloquent\Builder|Shop updatedDate($updatedDate)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereBackgroundImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereCloseTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereDeliveryRange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereLogoImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereMinAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereOpenTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop wherePercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereShowType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereStatusNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Shop whereVisibility($value)
 * @method static Builder|Shop withTrashed()
 * @method static Builder|Shop withoutTrashed()
 * @method whenLoaded(string $string)
 * @mixin Eloquent
 */
class Shop extends Model
{
    use HasFactory, SoftDeletes, Loadable, SetCurrency;
    protected $guarded = [];

    const STATUS = [
        'new',
        'edited',
        'approved',
        'rejected',
        'inactive'
    ];

    protected $casts = [
        'location' => 'array',
        'open_time' => 'datetime',
        'close_time' => 'datetime',
    ];

    public function getWorkingStatusAttribute($value): bool
    {
        return $this->open &&
            ($this->open_time <= now()->format('H:i') &&
                now()->format('H:i') <= $this->close_time)
            || $this->open_time == $this->close_time;
    }

    public function scopeUpdatedDate($query, $updatedDate)
    {
        return $query->where('updated_at', '>', $updatedDate);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ShopTranslation::class);
    }

    public function shopPayments(): HasMany
    {
        return $this->hasMany(ShopPayment::class);
    }

    public function translation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ShopTranslation::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'shop_id');
    }

    public function seller(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(User::class, Invitation::class,
            'shop_id', 'id', 'id', 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function shopProducts(): HasMany
    {
        return $this->hasMany(ShopProduct::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Order::class,
        'shop_id', 'reviewable_id');
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ShopSubscription::class, 'shop_id')
            ->whereDate('expired_at', '>=', today())->where(['active' => 1])->orderByDesc('id');
    }

    public function brands(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Brand::class,'shop_brands', 'shop_id', 'brand_id');
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class,'shop_categories', 'shop_id', 'category_id');
    }

    public function scopeFilter($value, $array)
    {
        return $value
            ->when(isset($array['user_id']), function ($q) use ($array) {
                $q->where('user_id', $array['user_id']);
            })
            ->when(isset($array['group_id']), function ($q) use ($array) {
                $q->where('group_id', $array['group_id']);
            })
            ->when(isset($array['status']), function ($q) use ($array) {
                $q->where('status', $array['status']);
            })
            ->when(isset($array['visibility']), function ($q) use ($array) {
                $q->where('visibility', $array['visibility']);
            })
            ->when(isset($array['open']), function ($q) use ($array) {
                $q->where('open_time', '<=', now()->format('H:i'))
                    ->where('close_time', '>=', now()->format('H:i'));
            })->when(isset($array['always_open']), function ($q) use ($array) {
                $q->whereColumn('open_time', '=', 'close_time');
            })
            ->when(data_get($array, 'search'), function ($q, $search) {
                $q->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->select('id', 'shop_id', 'locale', 'title');
                });

            })
            ->when(isset($array['delivery']), function ($q) use ($array) {
                $q->whereHas('deliveries', function ($q) use($array) {
                    if ($array['delivery'] == 'pickup'){
                        $q->where('type', $array['delivery']);
                    }else{
                        $q->where('type','!=' ,'pickup');
                    }
                });
            });
    }
}
