<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ShopBrandResource;
use App\Repositories\BrandRepository\BrandRepository;
use App\Repositories\ShopBrandRepository\ShopBrandRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends RestBaseController
{
    private ShopBrandRepository $shopBrandRepository;

    /**
     * @param ShopBrandRepository $shopBrandRepository
     */
    public function __construct(ShopBrandRepository $shopBrandRepository)
    {
        parent::__construct();
        $this->shopBrandRepository = $shopBrandRepository;
    }

    public function paginate(Request $request)
    {
        $brands = $this->shopBrandRepository->paginate($request->perPage ?? 15, true, $request->all());
        return ShopBrandResource::collection($brands);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $brand = $this->shopBrandRepository->show($id);
        if ($brand){
            return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), ShopBrandResource::make($brand));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
