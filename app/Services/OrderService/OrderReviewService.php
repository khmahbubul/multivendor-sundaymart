<?php

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Services\CoreService;

class OrderReviewService extends CoreService
{

    protected function getModelClass()
    {
        return Order::class;
    }

    public function addReview($id, $collection){
        $order = $this->model()->find($id);
        if ($order){
            $order->addReview($collection);
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $order];
        }
        return ['status' => false, 'code' => ResponseError::ERROR_404];
    }
}
