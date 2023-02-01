<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Resources\CurrencyResource;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CurrencyController extends RestBaseController
{
    private Currency $model;

    public function __construct(Currency $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function index()
    {
        $currencies = $this->model->where('active',1)->orderByDesc('default')->get();
        if ($currencies->count() > 0)
            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR), CurrencyResource::collection($currencies));
        else
            return $this->errorResponse(
                ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
                Response::HTTP_NOT_FOUND
            );
    }
}
