<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\Seller\BonusProduct\StoreRequest;
use App\Http\Requests\Seller\BonusProduct\UpdateRequest;
use App\Http\Resources\BonusProductResource;
use App\Repositories\BonusRepository\BonusProductRepository;
use App\Services\BonusService\BonusProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BonusProductController extends SellerBaseController
{
    private BonusProductService $service;
    private BonusProductRepository $repository;

    public function __construct(BonusProductRepository $repository, BonusProductService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $bonusShops = $this->repository->paginate($request->perPage ?? 15);
        return BonusProductResource::collection($bonusShops);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $collection = $request->validated();
        $result = $this->service->create($collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), BonusProductResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function show(int $id)
    {
        $shopBonus = $this->repository->show($id);
        if ($shopBonus) {
            return $this->successResponse(__('web.coupon_found'), BonusProductResource::make($shopBonus));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(int $id, UpdateRequest $request): JsonResponse
    {
        $collection = $request->validated();
        $result = $this->service->update($id, $collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), BonusProductResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteAllRequest $request): JsonResponse
    {
        $collection = $request->validated();
        $result = $this->service->delete($collection['ids']);
        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    public function statusChange(int $id)
    {
        $result = $this->service->statusChange($id);
        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_change'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }
}
