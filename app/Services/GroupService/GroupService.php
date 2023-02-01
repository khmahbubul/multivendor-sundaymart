<?php

namespace App\Services\GroupService;

use App\Helpers\ResponseError;
use App\Http\Requests\DeleteAllRequest;
use App\Models\Group;
use App\Models\RecipeCategory;
use App\Models\ShopPayment;
use App\Services\CoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupService extends CoreService
{

    protected function getModelClass()
    {
        return Group::class;
    }

    public function create($collection)
    {

        $model = $this->model()->create($collection);

        $this->setTranslations($model, $collection);

        return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
    }

    public function update($id,$collection)
    {
        $model = $this->model()->find($id);

        if ($model) {
            $model->update($collection);
            $this->setTranslations($model, $collection);
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $model];
        }
        return ['status' => false, 'code' => ResponseError::ERROR_404];
    }

    /**
     * @param array $ids
     * @return array
     */
    public function delete(array $ids): array
    {
        $items = $this->model()->whereDoesntHave('shops')->find($ids);

        if ($items->isNotEmpty()) {

            foreach ($items as $item) {
                $item->delete();
            }

            return ['status' => true, 'code' => ResponseError::NO_ERROR];
        }

        return ['status' => false, 'code' => ResponseError::ERROR_404];
    }

    public function setTranslations($model, $collection)
    {
        $model->translations()->delete();
        foreach ($collection['title'] as $index => $value) {
            $model->translation()->create([
                'title' => $value,
                'locale' => $index,
            ]);
        }
    }
}
