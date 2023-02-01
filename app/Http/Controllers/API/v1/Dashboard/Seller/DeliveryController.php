<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryCreateRequest;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Repositories\DeliveryRepository\DeliveryRepository;
use App\Services\DeliveryService\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DeliveryController extends SellerBaseController
{
    private DeliveryRepository $deliveryRepository;
    private DeliveryService $deliveryService;

    /**
     * @param DeliveryRepository $deliveryRepository
     * @param DeliveryService $deliveryService
     */
    public function __construct(DeliveryRepository $deliveryRepository, DeliveryService $deliveryService)
    {
        parent::__construct();
        $this->deliveryRepository = $deliveryRepository;
        $this->deliveryService = $deliveryService;
    }


    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $deliveries = $this->deliveryRepository->deliveriesList($this->shop->id, $request->active ?? null, $request->all());
        return $this->successResponse(trans('web.deliveries_list', [], \request()->lang), DeliveryResource::collection($deliveries));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DeliveryCreateRequest $request
     * @return JsonResponse
     */
    public function store(DeliveryCreateRequest $request)
    {
        $result = $this->deliveryService->create($request->merge(['shop_id' => $this->shop->id]));
        if ($result['status']) {
            return $this->successResponse(trans('web.record_successfully_created', [], $request->lang), DeliveryResource::make($result['data']));
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
    public function show(int $id)
    {
        $delivery = $this->deliveryRepository->deliveryDetails($id, $this->shop->id);
        if ($delivery) {
            $delivery->load('translations');

            return $this->successResponse(trans('web.delivery_found', [], \request()->lang), DeliveryResource::make($delivery));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );

    }

    /**
     * Update the specified resource in storage.
     *
     * @param DeliveryCreateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(DeliveryCreateRequest $request, int $id)
    {
        $delivery = Delivery::where('shop_id', $this->shop->id)->find($id);
        if ($delivery) {
            $result = $this->deliveryService->update($id, $request->merge(['shop_id' => $this->shop->id]));
            if ($result['status']) {
                return $this->successResponse(trans('web.record_successfully_updated', [], \request()->lang ?? 'en'), DeliveryResource::make($result['data']));
            }
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang ?? 'en'),
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, __('errors.' . ResponseError::ERROR_404, [], \request()->lang ?? 'en'),
            Response::HTTP_NOT_FOUND
        );

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Get Delivery types.
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function deliveryTypes()
    {
        return $this->successResponse(trans('web.delivery_types_list', [], \request()->lang), Delivery::TYPES);
    }

    /**
     * Change Active Status of Model.
     *
     * @param int $id
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function setActive(int $id)
    {
        $delivery = Delivery::where('shop_id', $this->shop->id)->find($id);
        if ($delivery) {
            $delivery->update(['active' => !$delivery->active]);

            return $this->successResponse(__('web.record_has_been_successfully_updated'), DeliveryResource::make($delivery));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );

    }
}
