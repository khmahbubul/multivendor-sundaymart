<?php

namespace App\Models;

use App\Traits\Loadable;
use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $uuid
 * @property string $firstname
 * @property string|null $lastname
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $birthday
 * @property string $gender
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $phone_verified_at
 * @property string|null $ip_address
 * @property int $active
 * @property string|null $img
 * @property string|null $firebase_token
 * @property string|null $password
 * @property string|null $remember_token
 * @property string|null $name_or_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|UserAddress[] $addresses
 * @property-read int|null $addresses_count
 * @property-read Collection|Gallery[] $galleries
 * @property-read int|null $galleries_count
 * @property-read mixed $role
 * @property-read Collection|Invitation[] $invitations
 * @property-read int|null $invitations_count
 * @property-read Invitation|null $invite
 * @property-read Shop|null $moderatorShop
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|OrderDetail[] $orderDetails
 * @property-read int|null $order_details_count
 * @property-read Collection|Order[] $orders
 * @property-read int|null $orders_count
 * @property-read Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read UserPoint|null $point
 * @property-read Collection|PointHistory[] $pointHistory
 * @property-read int|null $point_history_count
 * @property-read Collection|Role[] $roles
 * @property-read int|null $roles_count
 * @property-read Shop|null $shop
 * @property-read Collection|SocialProvider[] $socialProviders
 * @property-read int|null $social_providers_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @property-read Wallet|null $wallet
 * @method static UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirebaseToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereImg($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoneVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUuid($value)
 * @method static Builder|User withTrashed()
 * @method static Builder|User withoutTrashed()
 * @mixin Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use Loadable;
    use SoftDeletes;


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'birthday',
        'gender',
        'email',
        'phone',
        'img',
        'password',
        'firebase_token',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'role'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'birthday' => 'date',
    ];

    public function isOnline()
    {
        return Cache::has('user-online-' . $this->id);
    }

    public function getRoleAttribute(){
        return $this->role = $this->roles[0]->name ?? 'no role';
    }

    public function shop() {
        return $this->hasOne(Shop::class);
    }

    public function invite() {
        return $this->hasOne(Invitation::class);
    }

    public function moderatorShop() {
        return $this->hasOneThrough(Shop::class, Invitation::class,
            'user_id', 'id', 'id', 'shop_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function socialProviders()
    {
        return $this->hasMany(SocialProvider::class,'user_id','id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class,'user_id');
    }

    public function orderDetails()
    {
        return $this->hasManyThrough(OrderDetail::class,Order::class);
    }

    public function point()
    {
        return $this->hasOne(UserPoint::class, 'user_id');
    }

    public function pointHistory(): HasMany
    {
        return $this->hasMany(PointHistory::class, 'user_id');
    }

    public function getNameOrEmailAttribute(): ?string
    {
        return $this->firstname ?? $this->email;
    }
}
