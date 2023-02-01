<?php

namespace App\Http\Controllers\API\v1\Dashboard\Deliveryman;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use function request;

class OrderController extends DeliverymanBaseController
{
    private Order $model;

    public function __construct(Order $model)
    {
        parent::__construct();
        $this->model = $model;
    }

    public function paginate(FilterParamsRequest $request): AnonymousResourceCollection
    {
        $orders = $this->model
            ->with([
                'user',
                'transaction.paymentSystem.payment.translation' => fn($q) => $q->actualTranslation($request->lang ?? 'en'),
                'orderDetails.shopProduct.discount',
                'deliveryAddress',
                'shop.translation' => fn($q) => $q->actualTranslation($request->lang ?? 'en'),
                'orderDetails.shopProduct.product' => function ($q) {
                    $q->select('id', 'uuid');
                },
                'currency' => function ($q) {
                    $q->select('id', 'title', 'symbol');
                }])
            ->where('deliveryman',auth('sanctum')->id())
            ->updatedDate($request->updated_at ?? '2021-01-01')
            ->filter($request->all())
            ->orderBy($array['column'] ?? 'id', $array['sort'] ?? 'desc')
            ->paginate($request->perPage ?? 15);

        return OrderResource::collection($orders);
    }

    public function show(int $id)
    {
        $order = $this->model
            ->with([
                'user',
                'review',
                'deliveryType.translation' => fn($q) => $q->actualTranslation(request()->lang ?? 'en'),
                'deliveryAddress',
                'deliveryMan',
                'coupon',
                'shop.translation' => fn($q) => $q->actualTranslation(request()->lang ?? 'en'),
                'transaction.paymentSystem.payment' => function ($q) {
                    $q->select('id', 'tag', 'active');
                },
                'transaction.paymentSystem.payment.translation' => function ($q) {
                    $q->select('id', 'locale', 'payment_id', 'title')->actualTranslation(request()->lang ?? 'en');
                },
                'orderDetails.shopProduct.product.translation' => function ($q) {
                    $q->select('id', 'product_id', 'locale', 'title')->actualTranslation(request()->lang ?? 'en');
                },
                'currency' => function ($q) {
                    $q->select('id', 'title', 'symbol');
                }])
            ->where('deliveryman',auth('sanctum')->id())
            ->find($id);

        if ($order) {
            return $this->successResponse(__('web.order_found'), OrderResource::make($order));
        }

        return $this->errorResponse(
            ResponseError::ERROR_404,  trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update Order Status details by OrderDetail ID.
     *
     * @param int $order
     * @param FilterParamsRequest $request
     * @return JsonResponse
     */
    public function orderDetailStatusUpdate(int $order, FilterParamsRequest $request): JsonResponse
    {
        if (!in_array($request->status ?? null, ['ready', 'on_a_way', 'delivered'])) {
            return $this->errorResponse(
                ResponseError::ERROR_253, trans('errors.' . ResponseError::ERROR_253, [], request()->lang ?? 'en'),
                Response::HTTP_BAD_REQUEST
            );
        }

        $result = (new OrderService())->updateStatus($order, $request->status ?? null);

        if ($result['status']) {
            return $this->successResponse( __('errors.' . ResponseError::NO_ERROR), OrderDetailResource::make($result['data']));
        }

        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }
}
