<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Point\StoreRequest;
use App\Http\Requests\Admin\Point\UpdateRequest;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\PointResource;
use App\Models\Point;
use App\Services\PointService\PointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PointController extends AdminBaseController
{
    private Point $model;
    private PointService $pointService;

    /**
     * @param Point $model
     * @param PointService $pointService
     */
    public function __construct(Point $model,PointService $pointService)
    {
        parent::__construct();
        $this->model = $model;
        $this->pointService = $pointService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $points = $this->model
            ->orderBy($request->column ?? 'id', $request->sort ?? 'desc')
            ->paginate($request->perPage ?? 15);

        return PointResource::collection($points);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function store(StoreRequest $request)
    {
        $collection = $request->validated();
        $point = $this->model->create($collection);

        if ($point) {
            return $this->successResponse( __('web.record_was_successfully_create'), PointResource::make($point));
        }
        return $this->errorResponse(
            ResponseError::ERROR_400,  trans('errors.' . ResponseError::ERROR_400, [], \request()->lang ?? config('app.locale')),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse|AnonymousResourceCollection|Response
     */
    public function show(int $id)
    {
        $point = $this->model->find($id);
        if ($point) {
            return $this->successResponse(__('web.product_found'), PointResource::make($point));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404,  trans('errors.' . ResponseError::ERROR_404, [], request()->lang ?? config('app.locale')),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollection|Response
     */
    public function update(UpdateRequest $request, int $id)
    {
        $collection = $request->validated();
        $point = $this->model->find($id);
        if ($point) {
            $point->update($collection);
            return $this->successResponse(__('web.record_was_successfully_update'), PointResource::make($point));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404,  trans('errors.' . ResponseError::ERROR_404, [], request()->lang ?? config('app.locale')),
            Response::HTTP_NOT_FOUND
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

        $result = $this->pointService->delete($collection['ids']);

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
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function setActive(int $id)
    {
        $point = $this->model->find($id);
        if ($point) {
            $point->update(['active' => !$point->active]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), PointResource::make($point));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang ?? config('app.locale')),
            Response::HTTP_NOT_FOUND
        );
    }
}
