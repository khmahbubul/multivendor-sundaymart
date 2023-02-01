<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\RecipeCategory\StoreRequest;
use App\Http\Requests\RecipeCategory\UpdateRequest;
use App\Http\Resources\RecipeCategoryResource;
use App\Http\Resources\RecipeResource;
use App\Models\RecipeCategory;
use App\Repositories\RecipeCategoryRepository\RecipeCategoryRepository;
use App\Services\RecipeCategoryService\RecipeCategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\JsonResponse as JsonResponseAlias;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class RecipeCategoryController extends Controller
{
    use ApiResponse;
    private RecipeCategoryService $recipeCategoryService;
    private RecipeCategoryRepository $recipeCategoryRepository;

    /**
     * @param RecipeCategoryService $recipeCategoryService
     * @param RecipeCategoryRepository $recipeCategoryRepository
     */
    public function __construct(RecipeCategoryService $recipeCategoryService, RecipeCategoryRepository $recipeCategoryRepository)
    {
        $this->recipeCategoryService = $recipeCategoryService;
        $this->recipeCategoryRepository = $recipeCategoryRepository;
    }

    public function store(StoreRequest $request)
    {
        $collection = $request->validated();
            $result = $this->recipeCategoryService->create($collection);
            if ($result['status']) {
                return $this->successResponse(__('web.record_successfully_created'), $result['data']);
            }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );

    }

    public function update(UpdateRequest $request,int $id)
    {
        $collection = $request->validated();
        $result = $this->recipeCategoryService->update($collection,$id);
            if ($result['status']) {
                return $this->successResponse(__('web.record_successfully_updated'), $result['data']);
            }
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_BAD_REQUEST
            );
    }

    public function show(int $id)
    {
        $recipeCategory = $this->recipeCategoryRepository->getById($id);
        if ($recipeCategory){
            return $this->successResponse(__('web.coupon_found'), RecipeCategoryResource::make($recipeCategory));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $recipeCategory = $this->recipeCategoryRepository->paginate($request->perPage, $request->all());
        return RecipeCategoryResource::collection($recipeCategory);
    }

    public function statusChange(string $id)
    {
        $recipeCategory = $this->recipeCategoryRepository->getById($id);
        if ($recipeCategory) {
            $recipeCategory->update(['status' => !$recipeCategory->status]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), RecipeCategoryResource::make($recipeCategory));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
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

        $result = $this->recipeCategoryService->delete($collection['ids']);

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }
}
