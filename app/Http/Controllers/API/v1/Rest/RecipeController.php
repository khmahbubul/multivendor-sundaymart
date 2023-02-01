<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Repositories\RecipeRepository\RecipeRepository;
use App\Services\RecipeService\RecipeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class RecipeController extends Controller
{
    use ApiResponse;
    private RecipeRepository $recipeRepository;
    private recipeService $recipeService;

    public function __construct(RecipeRepository $recipeRepository, RecipeService $recipeService)
    {
        $this->recipeRepository = $recipeRepository;
        $this->recipeService = $recipeService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $recipes = $this->recipeRepository->paginate($request->perPage,$request->all(),$request->shop_id, true);
        return RecipeResource::collection($recipes);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id)
    {
        $recipe = $this->recipeRepository->getById($id);
        if ($recipe){
            return $this->successResponse(__('web.recipe_found'), RecipeResource::make($recipe));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
