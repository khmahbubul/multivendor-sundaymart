<?php

namespace App\Services\ShopPaymentService;

use App\Helpers\ResponseError;
use App\Models\ShopPayment;
use App\Services\CoreService;

class ShopPaymentService extends CoreService
{

    protected function getModelClass()
    {
        return ShopPayment::class;
    }

    public function create($collection): array
    {

        $model = $this->model()->create($collection);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];

    }

    public function update($collection,$id): array
    {
        $model = $this->model()->find($id);

        if ($model)
        {
            $model = $model->update($collection);
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        }
        return ['status' => false, 'code' => ResponseError::ERROR_404];
    }

}
