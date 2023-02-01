<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\Seller\Branch\StoreRequest;
use App\Http\Requests\Seller\Branch\UpdateRequest;
use App\Http\Resources\BranchResource;
use App\Repositories\BranchRepository\BranchRepository;
use App\Services\BranchService\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BranchController extends SellerBaseController
{
    private BranchRepository $repository;
    private BranchService $service;

    public function __construct(BranchRepository $repository, BranchService $service)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $branches = $this->repository->paginate($request->perPage ?? 15);
        return BranchResource::collection($branches);
    }

    /**
     * @param StoreRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function store(StoreRequest $request)
    {
        $collection = $request->validated();
        $result = $this->service->create($collection, $this->shop->id);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), BranchResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function show(int $id)
    {
        $branch = $this->repository->getById($id);
        if ($branch) {
            return $this->successResponse(__('web.coupon_found'), BranchResource::make($branch));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @param int $id
     * @param UpdateRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function update(int $id, UpdateRequest $request)
    {
        $collection = $request->validated();
        $result = $this->service->update($id, $collection, $this->shop->id);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), BranchResource::make($result['data']));
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
}
