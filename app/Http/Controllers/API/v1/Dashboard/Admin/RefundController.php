<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\ProductExport;
use App\Exports\RefundExport;
use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\Product\FileImportRequest;
use App\Http\Requests\Refund\IndexRequest;
use App\Http\Requests\Refund\StatisticsRequest;
use App\Http\Requests\Refund\StatusUpdateRequest;
use App\Http\Requests\Refund\UpdateRequest;
use App\Http\Resources\RefundResource;
use App\Imports\ProductsImport;
use App\Imports\RefundImport;
use App\Repositories\RefundRepository\RefundRepository;
use App\Services\RefundService\RefundService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\Response;

class RefundController extends Controller
{
    use ApiResponse;

    private RefundService $service;
    private RefundRepository $repository;

    public function __construct(RefundRepository $repository, RefundService $service)
    {
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param IndexRequest $request
     * @return AnonymousResourceCollection
     */

    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        $collection = $request->validated();
        $refunds = $this->repository->paginate($collection);
        return RefundResource::collection($refunds);
    }

    /**
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function show(int $id)
    {
        $refund = $this->repository->show($id);

        if ($refund) {
            return $this->successResponse(__('errors.' . ResponseError::NO_ERROR), RefundResource::make($refund));
        }

        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @param UpdateRequest $request
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function update(UpdateRequest $request, int $id)
    {
        $collection = $request->validated();

        $result = $this->service->update($collection, $id);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_update'), RefundResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @param StatusUpdateRequest $request
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function statusChange(StatusUpdateRequest $request, int $id)
    {
        $collection = $request->validated();

        $result = $this->service->statusChange($collection, $id);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_update'), RefundResource::make($result['data']));
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

        $result = $this->service->delete($collection['ids']);

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * @param StatisticsRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function statistics(StatisticsRequest $request)
    {
        $collection = $request->validated();
        $statistics = $this->repository->statisticsAdmin($collection['lang']);
        return $this->successResponse(__('web.record_has_been_successfully_found'), $statistics);
    }

    /**
     * @return JsonResponse|AnonymousResourceCollection
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function export()
    {
        $fileName = 'export/refund'.Str::slug(Carbon::now()->format('Y-m-d h:i:s')).'.xls';
        $file = Excel::store(new RefundExport(), $fileName, 'public');
        if ($file) {
            return $this->successResponse('Successfully exported', [
                'path' => 'public/export',
                'file_name' => $fileName
            ]);
        }
        return $this->errorResponse('Error during export');
    }

    /**
     * @param FileImportRequest $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function import(FileImportRequest $request)
    {
        $collection = $request->validated();
        try {
            Excel::import(new RefundImport(), $collection['file']);
            return $this->successResponse('Successfully imported');
        } catch (\Exception $exception) {
            return $this->errorResponse(ResponseError::ERROR_508, 'Excel format incorrect or data invalid');
        }
    }
}
