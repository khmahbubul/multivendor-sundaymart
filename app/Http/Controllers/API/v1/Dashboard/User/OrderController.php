<?php

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Requests\Order\StoreRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\User;
use App\Repositories\Interfaces\OrderRepoInterface;
use App\Services\Interfaces\OrderServiceInterface;
use App\Services\OrderService\OrderReviewService;
use App\Services\OrderService\OrderStatusUpdateService;
use App\Traits\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends UserBaseController
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
    public function paginate(FilterParamsRequest $request)
    {
        $orders = $this->orderRepository->ordersPaginate($request->perPage ?? 15,
            auth('sanctum')->id(), $request->all());
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

            // Select Admin Firebase Token to Push Notification
            $admins = User::whereHas('roles', function ($q) {
                $q->whereIn('role_id', [99, 21]);
            })->whereNotNull('firebase_token')->pluck('firebase_token');

            // Select Seller Firebase Token to Push Notification
            $sellers = User::whereHas('shop', function ($q) use($result){
                $q->whereIn('id', $result['data']->pluck('shop_id'));
            })->whereNotNull('firebase_token')->pluck('firebase_token');

            Log::info('Shop', [$result['data']->pluck('shop_id')]);
            Log::info('Seller', [$sellers]);
            Log::info('Tokens', [array_merge($admins->toArray(), $sellers->toArray())]);

            // Send notification about new Order.
            $this->sendNotification(array_merge($admins->toArray(), $sellers->toArray()), "New order was created", $result['data']->id);
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
    public function show(int $id)
    {
        $order = $this->orderRepository->show($id);
        if ($order && $order->user_id == auth('sanctum')->id()) {
            return $this->successResponse(ResponseError::NO_ERROR, OrderResource::make($order));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404,  trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Add Review to OrderDetails.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function addOrderReview(int $id, Request $request)
    {
        $result = (new OrderReviewService())->addReview($id, $request);
        if ($result['status']) {
            return $this->successResponse(ResponseError::NO_ERROR, OrderResource::make($result['data']));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Add Review to OrderDetails.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function orderStatusChange(int $id, Request $request)
    {
//        if (!isset($request->status) || $request->status != 'canceled') {
//            return $this->errorResponse(ResponseError::ERROR_253, trans('errors.' . ResponseError::ERROR_253, [], \request()->lang ?? config('app.locale')),
//                Response::HTTP_BAD_REQUEST
//            );
//        }

        $order = Order::find($id);
        if ($order) {
            $result = (new OrderStatusUpdateService())->statusUpdate($order, $request->status);
            if ($result['status']) {
                return $this->successResponse(ResponseError::NO_ERROR, OrderResource::make($result['data']));
            }
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
