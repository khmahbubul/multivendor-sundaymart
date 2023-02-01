<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Exports\RefundExport;
use App\Helpers\ResponseError;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\Product\FileImportRequest;
use App\Http\Requests\Refund\IndexRequest;
use App\Http\Requests\Refund\StatusUpdateRequest;
use App\Http\Requests\Refund\UpdateRequest;
use App\Http\Resources\RefundResource;
use App\Imports\RefundImport;
use App\Repositories\RefundRepository\RefundRepository;
use App\Services\RefundService\RefundService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as AnonymousResourceCollectionAlias;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpFoundation\Response;

class RefundController extends SellerBaseController
{
    use ApiResponse;

    private RefundService $service;
    private RefundRepository $repository;

    public function __construct(RefundRepository $repository, RefundService $service)
    {
        parent::__construct();
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param IndexRequest $request
     * @return AnonymousResourceCollectionAlias
     */

    public function index(IndexRequest $request)
    {
        $collection = $request->validated();
        $refunds = $this->repository->paginate($collection, $this->shop);
        return RefundResource::collection($refunds);
    }

    /**
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollectionAlias
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
     * @return JsonResponse|AnonymousResourceCollectionAlias
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
     * @return JsonResponse|AnonymousResourceCollectionAlias
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
     * @return AnonymousResourceCollectionAlias|JsonResponse
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
     * @return JsonResponse|AnonymousResourceCollectionAlias
     */
    public function statistics()
    {
        $statistics = $this->repository->statisticsShop($this->shop);
        return $this->successResponse(__('web.record_has_been_successfully_found'), $statistics);
    }

    /**
     * @return JsonResponse|AnonymousResourceCollectionAlias
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function export()
    {
        $fileName = 'export/refund' . Str::slug(Carbon::now()->format('Y-m-d h:i:s')) . '.xls';
        $file = Excel::store(new RefundExport($this->shop->id), $fileName, 'public');
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
     * @return JsonResponse|AnonymousResourceCollectionAlias
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
