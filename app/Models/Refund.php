<?php

namespace App\Models;

use App\Traits\Loadable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method whenLoaded(string $string)
 * @property mixed $id
 * @property mixed $order_id
 * @property mixed $user_id
 * @property mixed $status
 * @property mixed $message_seller
 * @property mixed $message_user
 * @property mixed $image
 * @property mixed $created_at
 * @property mixed $updated_at
 */
class Refund extends Model
{
    use HasFactory,Loadable,SoftDeletes;

    const PENDING = 'pending';
    const CANCELED = 'canceled';
    const ACCEPTED = 'accepted';

    const STATUS = [
        self::PENDING,
        self::CANCELED,
        self::ACCEPTED
    ];
    protected $fillable = ['order_id','user_id','status','message_seller','message_user', 'image'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilter($query,$collection)
    {
        $query->when(isset($collection['status']),function ($q) use ($collection){
            $q->where('status',$collection['status']);
        })->when(isset($collection['start_date']),function ($q) use ($collection){
            $q->whereDate('created_at','>=',$collection['start_date']);
        })->when(isset($collection['end_date']),function ($q) use ($collection){
            $q->whereDate('created_at','<=',$collection['end_date']);
        })->when(isset($collection['search']),function ($q) use ($collection){
            $q->whereHas('user',function ($q) use ($collection){
                $q->where('firstname', 'LIKE', '%' . $collection['search'] . '%')
                ->orWhere('lastname', 'LIKE', '%' . $collection['search'] . '%')
                ->orWhere('email', 'LIKE', '%' . $collection['search'] . '%')
                ->orWhere('phone', 'LIKE', '%' . $collection['search'] . '%');
            });
        });
    }

}
