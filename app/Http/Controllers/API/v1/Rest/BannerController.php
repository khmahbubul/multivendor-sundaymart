<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BannerResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ShopProductResource;
use App\Models\Banner;
use App\Models\Product;
use App\Models\ShopProduct;
use App\Repositories\BannerRepository\BannerRepository;
use App\Repositories\ProductRepository\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BannerController extends RestBaseController
{
    private BannerRepository $bannerRepository;

    /**
     * @param BannerRepository $bannerRepository
     * @param Banner $model
     */
    public function __construct(BannerRepository $bannerRepository, Banner $model)
    {
        parent::__construct();
        $this->bannerRepository = $bannerRepository;
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request)
    {
        $banners = $this->bannerRepository->bannersPaginateRest($request->perPage ?? 15, $request->all());
        return BannerResource::collection($banners);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $banner = $this->bannerRepository->bannerDetails($id);
        if ($banner){
            return $this->successResponse(__('web.banner_found'), BannerResource::make($banner));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }


    /**
     * Banner Products show .
     *
     * @param  int  $id
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function bannerProducts(int $id)
    {
        $banner = $this->bannerRepository->bannerDetails($id);
        if ($banner){
            $products = ShopProduct::with([
                'product.category.translation' => fn($q) => $q->actualTranslation(\request()->lang ?? 'en')
                    ->select('id', 'category_id', 'locale', 'title'),
                'product.brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'product.unit.translation' => fn($q) => $q->actualTranslation(\request()->lang ?? 'en'),
                'product.translation' => fn($q) => $q->actualTranslation(\request()->lang ?? 'en')
            ])
                ->whereIn('id', $banner->products)
                ->paginate(15);

            return ShopProductResource::collection($products);
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
