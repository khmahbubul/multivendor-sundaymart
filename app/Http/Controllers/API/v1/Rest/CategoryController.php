<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\ShopCategory;
use App\Repositories\Interfaces\CategoryRepoInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends RestBaseController
{

    private CategoryRepoInterface $categoryRepo;

    /**
     * @param CategoryRepoInterface $categoryRepo
     */
    public function __construct(CategoryRepoInterface $categoryRepo)
    {
        parent::__construct();
        $this->categoryRepo = $categoryRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */

    public function paginate(Request $request) {
        $categories = $this->categoryRepo->parentCategories($request->perPage ?? 15, true,  $request->all());
        return CategoryResource::collection($categories);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */

    public function selectPaginate(Request $request): AnonymousResourceCollection
    {

        $categories = $this->categoryRepo->selectPaginate($request->perPage ?? 15, true,  $request->all());

        return CategoryResource::collection($categories);
    }

    public function shopCategoryProduct(Request $request): AnonymousResourceCollection
    {
        $categoryIds = ShopCategory::where('shop_id', $request->input('shop_id', 0))->pluck('category_id');
        $categories = $this->categoryRepo->shopCategoryProduct($categoryIds, $request->all(),$request->perPage ?? 15);
        return CategoryResource::collection($categories);
    }


    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid)
    {
        $category = $this->categoryRepo->categoryByUuid($uuid);
        if ($category){
            return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), CategoryResource::make($category));
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
    public function categoriesSearch(Request $request)
    {
        $categories = $this->categoryRepo->categoriesSearch($request->search ?? '', true);
        return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), CategoryResource::collection($categories));
    }

    public function parentCategory(Request $request)
    {
        $categories = $this->categoryRepo->parentCategories($request->perPage,true,$request->all());
        return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), CategoryResource::collection($categories));
    }

    public function childrenCategory(Request $request,int $id)
    {
        $childrenCategories = $this->categoryRepo->childrenCategory($request->perPage ?? 15,$id);
        if ($childrenCategories){
            return $this->successResponse(__('errors.'. ResponseError::NO_ERROR), CategoryResource::collection($childrenCategories));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
