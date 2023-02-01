<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ShopPaymentResource;
use App\Models\Payment;
use App\Repositories\PaymentRepository\PaymentRepository;
use App\Repositories\ShopPaymentRepository\ShopPaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends RestBaseController
{
    private ShopPaymentRepository $shopPaymentRepository;

    /**
     * @param ShopPaymentRepository $shopPaymentRepository
     */
    public function __construct(ShopPaymentRepository $shopPaymentRepository)
    {
        $this->shopPaymentRepository = $shopPaymentRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $payments = $this->shopPaymentRepository->paginate($request->perPage ?? 15, $request->shop_id);
        return ShopPaymentResource::collection($payments);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function show(int $id)
    {
        $payment = $this->shopPaymentRepository->getById($id);
        if ($payment && $payment->status){
            return $this->successResponse(__('web.payment_found'), ShopPaymentResource::make($payment));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang ?? 'en'),
            Response::HTTP_NOT_FOUND
        );
    }

}
