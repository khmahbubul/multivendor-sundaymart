<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Cart
 *
 * @property int $id
 * @property int $shop_id
 * @property int $owner_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property float|null $total_price
 * @property int $status
 * @property-read User|null $user
 * @property-read UserCart|null $userCart
 * @property-read Collection|UserCart[] $userCarts
 * @property-read int|null $user_carts_count
 * @method static Builder|Cart newModelQuery()
 * @method static Builder|Cart newQuery()
 * @method static Builder|Cart query()
 * @method static Builder|Cart whereCreatedAt($value)
 * @method static Builder|Cart whereId($value)
 * @method static Builder|Cart whereOwnerId($value)
 * @method static Builder|Cart whereShopId($value)
 * @method static Builder|Cart whereStatus($value)
 * @method static Builder|Cart whereTotalPrice($value)
 * @method static Builder|Cart whereUpdatedAt($value)
 * @mixin Eloquent
 */
class Cart extends Model
{
    use HasFactory;
    protected $fillable = ['shop_id','uuid','owner_id','total_price','status','together'];

    public function user()
    {
        return $this->hasOne(User::class,'id','owner_id');
    }
    public function userCarts()
    {
        return $this->hasMany(UserCart::class);
    }

    public function userCart()
    {
        return $this->hasOne(UserCart::class);
    }
}
