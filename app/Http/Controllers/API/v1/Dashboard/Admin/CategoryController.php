<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\CategoryExport;
use App\Exports\ProductsExport;
use App\Helpers\ResponseError;
use App\Http\Requests\Admin\Category\FileImportRequest;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Imports\CategoryImport;
use App\Models\Category;
use App\Models\User;
use App\Repositories\Interfaces\CategoryRepoInterface;
use App\Services\CategoryServices\CategoryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Maatwebsite\Excel\Facades\Excel;

class CategoryController extends AdminBaseController
{
    private CategoryService $categoryService;
    private CategoryRepoInterface $categoryRepository;

    public function __construct(CategoryService $categoryService, CategoryRepoInterface $categoryRepository)
    {
        parent::__construct();
        $this->categoryRepository = $categoryRepository;
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryRepository->parentCategories($request->all());
        return $this->successResponse(__('web.categories_list'), CategoryResource::collection($categories));
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function paginate(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->parentCategories($request->perPage ?? 15, null, $request->all());
        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource with paginate.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function selectPaginate(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->selectPaginate($request->perPage ?? 15, null, $request->all());
        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @return JsonResponse
     */
    public function store(CategoryCreateRequest $request): JsonResponse
    {
        $result = $this->categoryService->create($request);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), []);
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $category = $this->categoryRepository->categoryByUuid($uuid);
        if ($category){
            $category->load('translations')->makeHidden('translation');
            return $this->successResponse(__('web.category_found'), CategoryResource::make($category));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryCreateRequest $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(string $uuid, CategoryCreateRequest $request): JsonResponse
    {
        $result = $this->categoryService->update($uuid, $request);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), []);
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

        $result = $this->categoryService->delete($collection['ids']);

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Remove Model image from storage.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function imageDelete(string $uuid): JsonResponse
    {
        $category = Category::firstWhere('uuid', $uuid);
        if ($category) {
            Storage::disk('public')->delete($category->img);
            $category->update(['img' => null]);

            return $this->successResponse(__('web.image_has_been_successfully_delete'), $category);
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Search Model by tag name.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function categoriesSearch(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->categoriesSearch($request->search ?? '');
        return CategoryResource::collection($categories);
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function setActive(string $uuid)
    {
        $category = $this->categoryRepository->categoryByUuid($uuid);
        if ($category) {
            $category->update(['active' => !$category->active]);

            return $this->successResponse(__('web.record_has_been_successfully_updated'), CategoryResource::make($category));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    public function fileExport()
    {
        $fileName = 'export/categories.xls';
        $file = Excel::store(new CategoryExport(), $fileName, 'public');
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
            Excel::import(new CategoryImport(), $collection['file']);
            return $this->successResponse('Successfully imported');
        } catch (Exception $exception) {
            return $this->errorResponse(ResponseError::ERROR_508,'Excel format incorrect or data invalid');
        }
    }
}
