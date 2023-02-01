<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\Admin\Banner\StoreRequest;
use App\Http\Requests\Admin\Banner\UpdateRequest;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Repositories\BannerRepository\BannerRepository;
use App\Services\BannerService\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BannerController extends SellerBaseController
{
    private BannerRepository $bannerRepository;
    private BannerService $bannerService;
    private Banner $model;

    public function __construct(BannerRepository $bannerRepository, BannerService $bannerService, Banner $banner)
    {
        parent::__construct();
        $this->bannerRepository = $bannerRepository;
        $this->bannerService = $bannerService;
        $this->model = $banner;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $banners = $this->bannerRepository->bannerPaginateSeller($request->perPage ?? 15, $this->shop->id);
        return BannerResource::collection($banners);
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

        $result = $this->bannerService->create($collection, $this->shop->id);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), BannerResource::make($result['data']));
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
        $banner = $this->bannerRepository->bannerDetails($id);
        if ($banner) {
            return $this->successResponse(__('web.banner_found'), BannerResource::make($banner));
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
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        $collection = $request->validated();
        $result = $this->bannerService->update($collection, $id, $this->shop->id);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updte'), BannerResource::make($result['data']));
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
     * @return Response
     */
    public function destroy(DeleteAllRequest $request): Response
    {
        $collection = $request->validated();

        $result = $this->bannerService->delete($collection['ids']);

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function setActiveBanner(int $id)
    {
        $banner = $this->model->find($id);
        if ($banner) {
            $banner->update(['active' => !$banner->active]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), BannerResource::make($banner));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
