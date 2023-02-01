<?php

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeCategoryResource;
use App\Repositories\RecipeCategoryRepository\RecipeCategoryRepository;
use App\Services\RecipeCategoryService\RecipeCategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    public function show(int $id)
    {
        $recipeCategory = $this->recipeCategoryRepository->getById($id, $active = true);
        if ($recipeCategory){
            return $this->successResponse(__('web.coupon_found'), RecipeCategoryResource::make($recipeCategory));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    public function index(Request $request)
    {
        $recipeCategory = $this->recipeCategoryRepository->paginate($request->perPage,$active = true);
        return RecipeCategoryResource::collection($recipeCategory);
    }
}
