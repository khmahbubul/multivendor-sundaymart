<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Requests\Admin\Order\StoreRequest;
use App\Http\Requests\Admin\Order\UpdateRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Interfaces\OrderRepoInterface;
use App\Services\Interfaces\OrderServiceInterface;
use App\Services\OrderService\OrderService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends AdminBaseController
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
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $orders = $this->orderRepository->ordersList();

        return OrderResource::collection($orders);
    }

    /**
     * Display a listing of the resource.
     *
     * @param FilterParamsRequest $request
     * @return AnonymousResourceCollection
     */
    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $orders = $this->orderRepository->ordersPaginate($request->perPage ?? 15, $request->user_id ?? null, $request->all());

        return OrderResource::collection($orders);
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
        $result = $this->orderService->create($collection);
        if ($result['status']) {
//             Select Seller Firebase Token to Push Notification

            $sellers = User::whereHas('shop', function ($q) use($result){
                $q->whereIn('id', $result['data']->pluck('shop_id'));
            })->whereNotNull('firebase_token')->pluck('firebase_token');

            $this->sendNotification($sellers->toArray(), "New order was created", $result['data']->id);
            return $this->successResponse( __('web.record_was_successfully_create'), OrderResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->show($id);
        if ($order) {
            return $this->successResponse(__('web.language_found'), OrderResource::make($order));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404,  trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
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
        $result = $this->orderService->update($id, $request);
        if ($result['status']) {
            return $this->successResponse( __('web.record_was_successfully_create'), OrderResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    public function orderStatusUpdate(int $id, FilterParamsRequest $request)
    {
            $order = Order::find($id);
            if ($order){
                $result = (new OrderService())->updateStatus($order, $request->status ?? null);
                if ($result['status']) {
                    return $this->successResponse( __('errors.' . ResponseError::NO_ERROR), OrderResource::make($result['data']));
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

    public function orderDeliverymanUpdate(int $id, Request $request)
    {
        $order = $this->orderRepository->getById($id);
        if ($order){
            $order->update([
                'deliveryman' => $request->deliveryman ?? $order->deliveryman,
            ]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), OrderResource::make($order));
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
    public function calculateOrderProducts(Request $request)
    {
        $result = $this->orderService->productsCalculate($request->all());
        return $this->successResponse(__('web.products_calculated'), $result);
    }

}
