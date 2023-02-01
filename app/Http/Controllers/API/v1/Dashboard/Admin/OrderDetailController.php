<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\OrderDetailResource;
use App\Models\User;
use App\Repositories\OrderRepository\OrderDetailRepository;
use App\Services\OrderService\OrderDetailService;
use App\Services\OrderService\OrderService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrderDetailController extends AdminBaseController
{
    use Notification;
    private OrderDetailRepository $detailRepository;

    /**
     * @param OrderDetailRepository $detailRepository
     */
    public function __construct(OrderDetailRepository $detailRepository)
    {
        parent::__construct();
        $this->detailRepository = $detailRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function paginate(Request $request): AnonymousResourceCollection
    {
        $orderDetails = $this->detailRepository->paginate($request->perPage, $request->user_id, $request->all());
        return OrderDetailResource::collection($orderDetails);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $orderDetail = $this->detailRepository->orderDetailById($id);
        if ($orderDetail) {
            return $this->successResponse(__('web.language_found'), OrderDetailResource::make($orderDetail));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404,  trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update Order Status details by OrderDetail ID.
     *
     * @param int $orderDetail
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function orderDetailStatusUpdate(int $orderDetail, FilterParamsRequest $request): JsonResponse
    {
        $result = (new OrderService())->updateStatus($orderDetail, $request->status ?? null);
        if ($result['status']) {

            // Select User Firebase Token to Push Notification
            $user = User::where('id', $result['data']->order->user_id)->pluck('firebase_token');
            $this->sendNotification($user->toArray(), "Your order status has been changed to $request->status", $result['data']->order->id);

            Log::info('USER TOKEN', $user->toArray());
            Log::info('ORDER USER', [$result['data']->order->user_id]);
            Log::info('ORDER DETAIL', [$result['data']]);
            Log::info('ORDER', [$result['data']->order]);

            return $this->successResponse( __('errors.' . ResponseError::NO_ERROR), OrderDetailResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Update Order Delivery details by OrderDetail ID.
     *
     * @param int $orderDetail
     * @param Request $request
     * @return JsonResponse
     */
    public function orderDetailDeliverymanUpdate(int $orderDetail, Request $request): JsonResponse
    {
        $orderDetail = $this->detailRepository->orderDetailById($orderDetail);
        if ($orderDetail){
            $orderDetail->update([
                'deliveryman' => $request->deliveryman ?? $orderDetail->deliveryman,
            ]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), OrderDetailResource::make($orderDetail));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], \request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Calculate products when cart updated.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateOrderProducts(Request $request): JsonResponse
    {
        $result = $this->detailRepository->orderProductsCalculate($request->all());
        return $this->successResponse(__('web.products_calculated'), $result);
    }
}
