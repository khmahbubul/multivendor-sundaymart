<?php

namespace App\Http\Controllers\API\v1\Dashboard\Payment;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\WalletResource;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\TransactionService\TransactionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends PaymentBaseController
{
    private Transaction $model;
    private TransactionService $transactionService;

    /**
     * @param Transaction $model
     * @param TransactionService $transactionService
     */
    public function __construct(Transaction $model, TransactionService $transactionService)
    {
        parent::__construct();
        $this->model = $model;
        $this->transactionService = $transactionService;
    }

    public function store(string $type, int $id, FilterParamsRequest $request)
    {
        switch ($type) {
            case 'order':
                $result = $this->transactionService->orderTransaction($id, $request);
                if ($result['status']) {
                    return $this->successResponse(__('web.record_successfully_created'), OrderResource::make($result['data']));
                }
                return $this->errorResponse(
                    $result['code'], __('errors.' . $result['code'], [], \request()->lang ?? 'en'),
                    Response::HTTP_NOT_FOUND
                );
            case 'subscription':
                $result = $this->transactionService->subscriptionTransaction($id, $request);

                if ($result['status']) {
                    return $this->successResponse(__('web.record_successfully_created'), SubscriptionResource::make($result['data']));
                }

                return $this->errorResponse(
                    $result['code'], __('errors.' . $result['code'], [], \request()->lang ?? 'en'),
                    Response::HTTP_NOT_FOUND
                );
            default:
                $result = $this->transactionService->walletTransaction($id, $request);

                if ($result['status']) {
                    return $this->successResponse(__('web.record_successfully_created'), WalletResource::make($result['data']));
                }

                return $this->errorResponse(
                    $result['code'], __('errors.' . $result['code'], [], \request()->lang ?? 'en'),
                    Response::HTTP_NOT_FOUND
                );
        }
    }

    public function updateStatus(string $type, int $id, Request $request)
    {
        $order = Order::with('orderDetails')->find($id);
        if ($order) {
            foreach ($order->orderDetails as $detail) {
                $this->model->where('payable_id', $detail->id)->update([
                    'status' => $request->status,
                    'payment_trx_id' => $request->status,
                ]);
            }
            return $this->successResponse(__('web.record_successfully_created'), OrderResource::make($order));

        } else {
            return $this->errorResponse(
                ResponseError::ERROR_404, __('errors.' . ResponseError::ERROR_404, [], \request()->lang ?? 'en'),
                Response::HTTP_NOT_FOUND
            );
        }
    }


}
