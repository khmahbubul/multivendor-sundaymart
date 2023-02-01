<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Repositories\UnitRepository\UnitRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class UnitController extends SellerBaseController
{
    private UnitRepository $unitRepository;

    /**
     * @param UnitRepository $unitRepository
     */
    public function __construct(UnitRepository $unitRepository)
    {
        parent::__construct();
        $this->unitRepository = $unitRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function paginate(Request $request): AnonymousResourceCollection
    {
        $units = $this->unitRepository->unitsPaginate($request->perPage ?? 15, $request->active ?? null, $request->all());
        return UnitResource::collection($units);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitRepository->unitDetails($id);
        if ($unit){
            return $this->successResponse(__('web.unit_found'), UnitResource::make($unit));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
