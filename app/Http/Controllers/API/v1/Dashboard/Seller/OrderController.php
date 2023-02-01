<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Seller\Order\StoreRequest;
use App\Http\Requests\Seller\Order\UpdateRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Repositories\Interfaces\OrderRepoInterface;
use App\Services\Interfaces\OrderServiceInterface;
use App\Services\OrderService\OrderDetailService;
use App\Services\OrderService\OrderService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends SellerBaseController
{
    use Notification;

    private OrderRepoInterface $orderRepository;
    private OrderServiceInterface $orderService;

    /**
     * @param OrderRepoInterface $orderRepository
     * @param OrderServiceInterface $orderService
     */
    public function __construct(OrderRepoInterface $orderRepository, OrderServiceInterface $orderService)
    {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function paginate(FilterParamsRequest $request)
    {
        $orders = $this->orderRepository->ordersPaginate(
            $request->perPage ?? 15, $request->user_id ?? null,
            $request->merge(['shop_id' => $this->shop->id])->all());

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(StoreRequest $request)
    {
        $collection = $request->validated();

        $result = $this->orderService->create($collection);

        if ($result['status']) {

            $admins = User::whereHas('roles', function ($q) {
                $q->whereIn('role_id', [99, 21]);
            })->pluck('firebase_token');
            Log::info("SELLER NOTIFICATION", $admins->toArray());

            $this->sendNotification($admins->toArray(), "New order was created", $result['data']->id);
            return $this->successResponse(__('web.record_was_successfully_create'), OrderResource::make($result['data']));
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
        $order = $this->orderRepository->show($id, $this->shop->id);
        if ($order) {
            return $this->successResponse(__('web.order_found'), OrderResource::make($order));
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
     * @param int $id
     * @return JsonResponse
     */
    public function update(int $id, UpdateRequest $request)
    {
        $collection = $request->validated();
        $result = $this->orderService->update($id, $collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_successfully_create'), OrderResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Update Order Status details by OrderDetail ID.
     *
     * @param int $orderDetail
     * @param Request $request
     * @return JsonResponse
     */
    public function orderStatusUpdate(int $order, FilterParamsRequest $request)
    {
        $order = Order::where('shop_id', $this->shop->id)->find($order);
        if ($order) {
            $result = (new OrderService())->updateStatus($order, $request->status ?? null);
            if ($result['status']) {
                return $this->successResponse(__('errors.' . ResponseError::NO_ERROR), OrderResource::make($result['data']));
            }
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang ?? 'en'),
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], \request()->lang ?? 'en'),
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
    public function orderDetailDeliverymanUpdate(int $order, Request $request)
    {
        $order = Order::where('shop_id', $this->shop->id)->find($order);
        if ($order) {
            $order->update([
                'deliveryman' => $request->deliveryman ?? $order->deliveryman,
            ]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), OrderResource::make($order));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], \request()->lang ?? 'en'),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Calculate products when cart updated.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateOrderProducts(Request $request)
    {
        $result = $this->orderService->productsCalculate($request->all());
        return $this->successResponse(__('web.products_calculated'), $result);
    }

}
