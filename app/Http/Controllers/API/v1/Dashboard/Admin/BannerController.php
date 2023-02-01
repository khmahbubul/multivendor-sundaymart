<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BannerController extends AdminBaseController
{
    private BannerRepository $bannerRepository;
    private BannerService $bannerService;
    private Banner $model;

    public function __construct(BannerRepository $bannerRepository,BannerService $bannerService,Banner $banner)
    {
        parent::__construct();
        $this->bannerRepository = $bannerRepository;
        $this->bannerService = $bannerService;
        $this->model = $banner;
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
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function store(StoreRequest $request)
    {
        $collection = $request->validated();

        $result = $this->bannerService->create($collection);

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
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, int $id)
    {
        $collection = $request->validated();
        $result = $this->bannerService->update($collection, $id);

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
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function destroy(DeleteAllRequest $request)
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
    public function setActiveBanner(int $id): JsonResponse
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
