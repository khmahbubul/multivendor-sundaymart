<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\GroupStoreRequest;
use App\Http\Requests\Cart\IndexRequest;
use App\Http\Requests\Cart\OpenCartRequest;
use App\Http\Requests\Cart\StoreRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\UserCartResource;
use App\Repositories\CartRepository\CartRepository;
use App\Services\CartService\CartService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    use ApiResponse;
    private CartRepository $cartRepository;
    private CartService $cartService;

    public function __construct(CartRepository $cartRepository, CartService $cartService)
    {
        $this->cartRepository = $cartRepository;
        $this->cartService = $cartService;
    }

    public function get(int $id,IndexRequest $request)
    {
        $collection = $request->validated();
        $cart = $this->cartRepository->get($collection['shop_id'],$id);
        if ($cart){
            return $this->successResponse(__('web.record_was_found'), CartResource::make($cart));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    public function openCart(OpenCartRequest $request)
    {
        $collection = $request->validated();
        $result = $this->cartService->openCart($collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_successfully_create'), UserCartResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    public function store(GroupStoreRequest $request)
    {
        $collection = $request->validated();

        $result = $this->cartService->groupCreate($collection);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_successfully_create'), CartResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'] , ['quantity' => $result['data'] ?? null], \request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    public function userCartDelete(string $user_cart_uuid, Request $request)
    {
        $result = $this->cartService->userCartDelete($user_cart_uuid, $request->cart_id);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_successfully_delete'));
        } else {
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function cartProductDelete(int $cart_detail_id)
    {
        $result = $this->cartService->cartProductDelete($cart_detail_id);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_successfully_delete'));
        } else {
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function statusChange(string $user_cart_uuid,Request $request)
    {
        $result = $this->cartService->statusChange($user_cart_uuid, $request->cart_id);
        if ($result['status']) {
            return $this->successResponse(__('web.record_was_status changed'),UserCartResource::make($result['data']) );
        } else {
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_NOT_FOUND
            );
        }

    }
}
