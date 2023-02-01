<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\ShopCategory\StoreRequest;
use App\Http\Requests\ShopCategory\UpdateRequest;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepository\CategoryRepository;
use App\Repositories\ShopCategoryRepository\ShopCategoryRepository;
use App\Services\ShopCategoryService\ShopCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ShopCategoryController extends SellerBaseController
{


    private ShopCategoryRepository $shopCategoryRepository;
    private CategoryRepository $categoryRepository;
    private ShopCategoryService $shopCategoryService;

    public function __construct(ShopCategoryRepository $shopCategoryRepository, ShopCategoryService $shopCategoryService, CategoryRepository $categoryRepository)
    {
        parent::__construct();
        $this->shopCategoryRepository = $shopCategoryRepository;
        $this->shopCategoryService = $shopCategoryService;
        $this->categoryRepository = $categoryRepository;
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $shopBrands = $this->categoryRepository->shopCategory($request->perPage, $this->shop->id);
        return CategoryResource::collection($shopBrands);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function paginate(Request $request)
    {
        $shopBrands = $this->categoryRepository->shopCategoryPaginate($request->perPage);
        return CategoryResource::collection($shopBrands);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function selectPaginate(Request $request)
    {

        $shopBrands = $this->categoryRepository->selectPaginate($request->perPage);

        return CategoryResource::collection($shopBrands);
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
        $collection['shop_id'] = $this->shop->id;
        $result = $this->shopCategoryService->create($collection);
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
    public function show(int $id): JsonResponse
    {

        $shopBrand = $this->categoryRepository->shopCategoryById($id, $this->shop->id);
        if ($shopBrand) {
            return $this->successResponse(__('web.coupon_found'), CategoryResource::make($shopBrand));
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
    public function update(UpdateRequest $request): JsonResponse
    {

        $collection = $request->validated();
        $collection['shop_id'] = $this->shop->id;
        $result = $this->shopCategoryService->update($collection, $this->shop);
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
        $result = $this->shopCategoryService->delete($collection['ids']);
        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    public function allCategory(Request $request)
    {
        $category = $this->categoryRepository->shopCategoryNonExistPaginate($this->shop->id, $request->all(), $request->perPage ?? 10);
        if ($category) {
            return CategoryResource::collection($category);
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, __('errors.' . ResponseError::ERROR_404, [], \request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
