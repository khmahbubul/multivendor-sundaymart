<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Resources\PaymentResource;
use App\Repositories\Interfaces\PaymentRepoInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends SellerBaseController
{
    private PaymentRepoInterface $paymentRepository;

    /**
     * @param PaymentRepoInterface $paymentRepository
     */
    public function __construct(PaymentRepoInterface $paymentRepository)
    {
        parent::__construct();
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function paginate(Request $request)
    {
        $products = $this->paymentRepository->paginate($request->input('perPage', 15), $request->all());
        return PaymentResource::collection($products);
    }

}
