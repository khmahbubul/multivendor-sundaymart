<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;


use App\Exports\ProductsExport;
use App\Helpers\ResponseError;
use App\Http\Requests\Product\FileImportRequest;
use App\Http\Requests\Product\StoreRequest;
use App\Http\Requests\Product\UpdateRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSearchResource;
use App\Imports\ProductsImport;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepoInterface;
use App\Services\ProductService\ProductAdditionalService;
use App\Services\ProductService\ProductService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AdminBaseController
{
    private ProductService $productService;
    private ProductRepoInterface $productRepository;

    /**
     * @param ProductService $productService
     * @param ProductRepoInterface $productRepository
     */
    public function __construct(ProductService $productService, ProductRepoInterface $productRepository)
    {
        parent::__construct();
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    public function paginate(Request $request)
    {
        $products = $this->productRepository->productsPaginate($request->perPage ?? 15, $request->active, $request->all());
        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request)
    {
        $collection = $request->validated();
        $result = $this->productService->create($collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_successfully_create'), ProductResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid)
    {
        $product = $this->productRepository->productByUUID($uuid);
        if ($product) {
            return $this->successResponse(__('web.product_found'), ProductResource::make($product->load('translations')));
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
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(UpdateRequest $request, int $id)
    {
        $collection = $request->validated();
        $result = $this->productService->update($id, $collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_successfully_update'), ProductResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function destroy(string $uuid)
    {
        $result = $this->productService->destroy($uuid);

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Add Product Properties.
     *
     * @param string $uuid
     * @param Request $request
     * @return JsonResponse
     */
    public function addProductProperties(string $uuid, Request $request)
    {
        $result = (new ProductAdditionalService())->createOrUpdateProperties($uuid, $request->all());

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_created'), ProductResource::make($result['data']));
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
    public function productsSearch(Request $request): AnonymousResourceCollection
    {
        $categories = $this->productRepository->productsSearch($request->input('perPage', 15), true, $request->all());
        return ProductSearchResource::collection($categories);
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function setActive(string $uuid)
    {
        $product = $this->productRepository->productByUUID($uuid);
        if ($product) {
            $product->update(['active' => !$product->active]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), ProductResource::make($product));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    public function fileExport()
    {
        $fileName = 'export/products.xls';
        $file = Excel::store(new ProductsExport(), $fileName, 'public');
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
            Excel::import(new ProductsImport(), $collection['file']);
            return $this->successResponse('Successfully imported');
        } catch (Exception $exception) {
            return $this->errorResponse(ResponseError::ERROR_508, 'Excel format incorrect or data invalid');
        }
    }

    public function deleteAll(Request $request)
    {
        $result = $this->productService->deleteAll($request->productIds);
        if ($result)
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

}
