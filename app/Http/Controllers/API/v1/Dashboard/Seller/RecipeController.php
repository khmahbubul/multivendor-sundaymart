<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\CategoryCreateRequest;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\Recipe\StoreRequest;
use App\Http\Requests\Recipe\UpdateRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Repositories\RecipeRepository\RecipeRepository;
use App\Services\RecipeService\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as AnonymousResourceCollectionAlias;
use Symfony\Component\HttpFoundation\Response;

class RecipeController extends SellerBaseController
{
    private RecipeRepository $recipeRepository;
    private RecipeService $recipeService;

    public function __construct(RecipeRepository $recipeRepository, RecipeService $recipeService)
    {
        parent::__construct();
        $this->recipeRepository = $recipeRepository;
        $this->recipeService = $recipeService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $recipe = $this->recipeRepository->paginate($request->perPage, $request->all(), $this->shop->id);
        return RecipeResource::collection($recipe);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $collection = $request->validated();
        $collection['shop_id'] = $this->shop->id;
        $result = $this->recipeService->create($collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), $result['data']);
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {

        $recipe = $this->recipeRepository->getById($id, $this->shop->id);
        if ($recipe) {
            return $this->successResponse(__('web.coupon_found'), RecipeResource::make($recipe));
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
     * @param UpdateRequest $request
     * @return JsonResponse
     */
    public function update(int $id, UpdateRequest $request): JsonResponse
    {
        $collection = $request->validated();
        $result = $this->recipeService->update($id, $collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), $result['data']);
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
        $result = $this->recipeService->delete($collection['ids']);
        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    public function statusChange(string $id)
    {
        $recipe = $this->recipeRepository->getById($id);
        if ($recipe) {
            $recipe->update(['status' => !$recipe->status]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), RecipeResource::make($recipe));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
