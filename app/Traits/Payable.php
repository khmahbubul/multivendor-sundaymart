<?php

namespace App\Traits;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Payable
{
    public function createTransaction($transaction)
    {
       return $this->transactions()->updateOrCreate(['payable_id' => $this->id], [
            'price' => $transaction['price'],
            'user_id' => $transaction['user_id'] ?? auth('sanctum')->id(),
            'payment_sys_id' => $transaction['payment_sys_id'],
            'payment_trx_id' => $transaction['payment_trx_id'] ?? null,
            'note' => $transaction['note'] ?? '',
            'perform_time' => $transaction['perform_time'] ?? now(),
            'status_description' => $transaction['status_description'] ?? "Transaction in progress",
        ]);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payable');
    }
}
