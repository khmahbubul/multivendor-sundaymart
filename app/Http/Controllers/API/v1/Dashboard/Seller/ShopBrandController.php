<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\ShopBrand\StoreRequest;
use App\Http\Requests\ShopBrand\UpdateRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ShopBrandResource;
use App\Repositories\BrandRepository\BrandRepository;
use App\Repositories\ShopBrandRepository\ShopBrandRepository;
use App\Repositories\ShopRepository\ShopRepository;
use App\Services\ShopBrandService\ShopBrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ShopBrandController extends SellerBaseController
{
    private ShopBrandService $shopBrandService;
    private ShopBrandRepository $shopBrandRepository;
    private BrandRepository $brandRepository;


    public function __construct(ShopBrandRepository $shopBrandRepository, ShopBrandService $shopBrandService, BrandRepository $brandRepository)
    {
        parent::__construct();
        $this->shopBrandRepository = $shopBrandRepository;
        $this->shopBrandService = $shopBrandService;
        $this->brandRepository = $brandRepository;
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $shopBrands = $this->brandRepository->shopBrand($request->perPage, $this->shop->id);
        return BrandResource::collection($shopBrands);
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function paginate(Request $request)
    {
        $shopBrands = $this->brandRepository->shopBrandPaginate($request->perPage);
        return BrandResource::collection($shopBrands);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request)
    {
        $collection = $request->validated();
        $collection['shop_id'] = $this->shop->id;
        $result = $this->shopBrandService->create($collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), $result['data']);
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
     * @return JsonResponse
     */
    public function show(int $id)
    {

        $shopBrand = $this->brandRepository->shopBrandById($id, $this->shop->id);
        if ($shopBrand) {
            return $this->successResponse(__('web.coupon_found'), BrandResource::make($shopBrand));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(UpdateRequest $request)
    {

        $collection = $request->validated();
        $collection['shop_id'] = $this->shop->id;
        $result = $this->shopBrandService->update($collection, $this->shop);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), $result['data']);
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
        $result = $this->shopBrandService->delete($collection['ids']);
        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    public function allBrand(Request $request)
    {
        $brand = $this->brandRepository->shopBrandNonExistPaginate($this->shop->id, $request->all(), $request->perPage ?? 10);
        if ($brand) {
            return BrandResource::collection($brand);
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, __('errors.' . ResponseError::ERROR_404, [], \request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
