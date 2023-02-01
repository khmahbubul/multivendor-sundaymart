<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\Seller\Discount\StoreRequest;
use App\Http\Requests\Seller\Discount\UpdateRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use App\Repositories\DiscountRepository\DiscountRepository;
use App\Services\DiscountService\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DiscountController extends SellerBaseController
{
    private DiscountRepository $discountRepository;
    private DiscountService $discountService;

    /**
     * @param DiscountRepository $discountRepository
     * @param DiscountService $discountService
     */
    public function __construct(DiscountRepository $discountRepository, DiscountService $discountService)
    {
        parent::__construct();
        $this->discountRepository = $discountRepository;
        $this->discountService = $discountService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function paginate(Request $request)
    {
        $discounts = $this->discountRepository->discountsPaginate(
            $request->perPage ?? 15, $this->shop->id, $request->active ?? null, $request->all()
        );
        return DiscountResource::collection($discounts);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function store(StoreRequest $request)
    {
        $collection = $request->validated();
        if ($this->shop) {
            $collection['shop_id'] = $this->shop->id;
            $result = $this->discountService->create($collection);
            if ($result['status']) {
                return $this->successResponse(__('web.record_successfully_created'), DiscountResource::make($result['data']));
            }
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->errorResponse(
            ResponseError::ERROR_101, __('errors.' . ResponseError::ERROR_101, [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function show(int $id)
    {
        if ($this->shop) {
            $discount = $this->discountRepository->discountDetails($id, $this->shop->id);
            if ($discount) {
                return $this->successResponse(__('web.discount_found'), DiscountResource::make($discount));
            }
            return $this->errorResponse(
                ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang ?? 'en'),
                Response::HTTP_NOT_FOUND
            );
        }
        return $this->errorResponse(
            ResponseError::ERROR_101, __('errors.' . ResponseError::ERROR_101, [], \request()->lang ?? 'en'),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param int $id
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function update(UpdateRequest $request, int $id)
    {
        $collection = $request->validated();
        if (!$this->shop) {
            return $this->errorResponse(
                ResponseError::ERROR_101, __('errors.' . ResponseError::ERROR_101, [], \request()->lang),
                Response::HTTP_BAD_REQUEST
            );
        }

        $collection['shop_id'] = $this->shop->id;
        $result = $this->discountService->update($id, $collection);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), DiscountResource::make($result['data']));
        }

        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DeleteAllRequest $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function destroy(DeleteAllRequest $request)
    {
        $collection = $request->validated();
        $result = $this->discountService->delete($collection['ids']);
        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );

    }

    public function setActiveStatus($id)
    {
        $discount = Discount::firstWhere(['id' => $id, 'shop_id' => $this->shop->id]);
        if ($discount) {
            $discount->update(['active' => !$discount->active]);
            return $this->successResponse(__('web.record_active_update'), DiscountResource::make($discount));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, __('errors.' . ResponseError::ERROR_404, [], \request()->lang ?? 'en'),
            Response::HTTP_NOT_FOUND
        );
    }
}
