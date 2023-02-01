<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\BrandExport;
use App\Exports\CategoryExport;
use App\Helpers\ResponseError;
use App\Http\Requests\Admin\Brand\FileImportRequest;
use App\Http\Requests\BrandCreateRequest;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Resources\BrandResource;
use App\Imports\BrandImport;
use App\Imports\CategoryImport;
use App\Models\Brand;
use App\Repositories\BrandRepository\BrandRepository;
use App\Services\Interfaces\BrandServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends AdminBaseController
{
    private BrandRepository $brandRepository;
    private BrandServiceInterface $brandService;

    /**
     * @param BrandRepository $brandRepository
     * @param BrandServiceInterface $brandService
     */
    public function __construct(BrandRepository $brandRepository, BrandServiceInterface $brandService)
    {
        parent::__construct();
        $this->brandRepository = $brandRepository;
        $this->brandService = $brandService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $brands = $this->brandRepository->brandsList(request()->all());
        return $this->successResponse(__('web.brands_list'), BrandResource::collection($brands));
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function paginate(Request $request): AnonymousResourceCollection
    {
        $brands = $this->brandRepository->brandsPaginate($request->perPage ?? 15, null, $request->all());
        return BrandResource::collection($brands);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param BrandCreateRequest $request
     * @return JsonResponse
     */
    public function store(BrandCreateRequest $request): JsonResponse
    {
        $result = $this->brandService->create($request);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), BrandResource::make($result['data']));
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
    public function show(int $id): JsonResponse
    {
        $brand = $this->brandRepository->brandDetails($id);

        if ($brand){
            return $this->successResponse(__('web.brand_found'), BrandResource::make($brand));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     * @param BrandCreateRequest $request
     * @return JsonResponse
     */
    public function update(int $id, BrandCreateRequest $request): JsonResponse
    {
        $result = $this->brandService->update($id, $request);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), BrandResource::make($result['data']));
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

        $result = $this->brandService->delete($collection['ids']);

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }


    /**
     * Search Model by tag name.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function brandsSearch(Request $request): AnonymousResourceCollection
    {
        $brands = $this->brandRepository->brandsSearch($request->search ?? '');
        return BrandResource::collection($brands);
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @return BrandResource
     */
    public function setActive(int $id): BrandResource
    {
        $brand = $this->brandRepository->brandDetails($id);
        $brand->update(['active' => !$brand->active]);

        return BrandResource::make($brand);
    }

    public function fileExport()
    {
        $fileName = 'export/brands.xls';
        $file = Excel::store(new BrandExport(), $fileName, 'public');
        if ($file) {
            return $this->successResponse('Successfully exported', [
                'path' => 'public/export',
                'file_name' => $fileName
            ]);
        }
        return $this->errorResponse('Error during export');
    }

    public function fileImport(FileImportRequest $request)
    {
        $collection = $request->validated();
        try {
            Excel::import(new BrandImport(), $collection['file']);
            return $this->successResponse('Successfully imported');
        } catch (Exception $exception) {
            return $this->errorResponse(ResponseError::ERROR_508,'Excel format incorrect or data invalid');
        }
    }

}
