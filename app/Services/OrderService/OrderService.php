<?php

namespace App\Services\OrderService;

use App\Helpers\ResponseError;
use App\Models\BonusShop;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Services\CoreService;
use App\Services\Interfaces\OrderServiceInterface;
use App\Services\ShopProductService\ShopProductService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderService extends CoreService implements OrderServiceInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->lang = request('lang') ?? 'en';
    }

    protected function getModelClass()
    {
        return Order::class;
    }

    public function create($collection)
    {
        $shop = Shop::find($collection['shop_id']);
        /** @var Order $order */
        $order = $this->model()->create($this->setOrderParams($collection));

        if ($order) {

            if (data_get($collection, 'cart_id')) {
                (new OrderDetailService())->createOrderUser($order, data_get($collection, 'cart_id'));
            } else {
                (new OrderDetailService())->create($order, data_get($collection, 'products', []));
                (new ShopProductService())->decrementStocksQuantity(data_get($collection, 'products'));
            }

            $orderDetails = $order->orderDetails();

            $tax = ($orderDetails->sum('origin_price') / 100) * data_get($order, 'shop.tax', 1) + $orderDetails->sum('tax');

            $totalPrice = $orderDetails->sum('origin_price') + $order->delivery_fee + $tax;

            $this->checkCoupon($collection['coupon'] ?? null, $order);

            $totalPrice = max($totalPrice, 0);

            $order->update([
                'price' => $totalPrice,
                'commission_fee' => ($totalPrice / 100 * $shop->percentage <= 0.99 ? 1 : $shop->percentage),
                'total_discount' => $orderDetails->sum('discount'),
                'tax' => $tax
            ]);

            return ['status' => true, 'message' => ResponseError::NO_ERROR, 'data' => $order];
        }
        return ['status' => false, 'code' => ResponseError::ERROR_501];
    }


    public function update(int $id, $collection)
    {
        $shop = Shop::find($collection['shop_id']);
        try {
            $order = $this->model()->find($id);
            if ($order) {
                $collection['commission_fee'] = ($collection['total'] / 100 * $shop->percentage);
                $order->update($this->setOrderParams($collection));
                (new ShopProductService())->incrementStocksQuantity($collection['products']);
                (new OrderDetailService())->create($order, $collection['products']);
                (new ShopProductService())->decrementStocksQuantity($collection['products']);

                return ['status' => true, 'message' => ResponseError::NO_ERROR, 'data' => $order];
            }
            return ['status' => false, 'code' => ResponseError::ERROR_501];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    private function setOrderParams($collection): array
    {
        return [
            'user_id' => auth('sanctum')->id(),
            'price' => 0,
            'currency_id' => $collection['currency_id'] ?? Currency::whereDefault(1)->pluck('id')->first(),
            'rate' => $collection['rate'],
            'note' => $collection['note'] ?? null,
            'shop_id' => $collection['shop_id'],
            'status' => $collection['status'] ?? 'new',
            'delivery_type_id' => $collection['delivery_type_id'] ?? null,
            'delivery_fee' => $collection['delivery_fee'] / $collection['rate'],
            'delivery_address_id' => $collection['delivery_address_id'] ?? null,
            'deliveryman' => $collection['deliveryman'] ?? null,
            'delivery_date' => $collection['delivery_date'] ?? null,
            'delivery_time' => $collection['delivery_time'] ?? null,
            'total_discount' => $collection['total_discount'] ?? null
        ];
    }

    private function checkCoupon($coupon, $order)
    {
        if (isset($coupon)) {
            $result = Coupon::checkCoupon($coupon)->first();
            if ($result) {
                switch ($result->type) {
                    case 'percent':
                        $couponPrice = ($order->price / 100) * $result->price;
                        break;
                    default:
                        $couponPrice = $result->price;
                        break;
                }
                $order->update(['price' => $order->price - $couponPrice]);
                $order->coupon()->create([
                    'user_id' => $order->user_id,
                    'name' => $result->name,
                    'price' => $couponPrice,
                ]);
                $result->decrement('qty');
            }
        }
    }

    public function updateStatus($order, $status): array
    {
        // Order Status change logic
        return (new OrderStatusUpdateService())->statusUpdate($order, $status);

    }

    public function orderProductsCalculate(int $id)
    {
        // Get Product ID from Request
//        $id = collect($array['products'])->pluck('id');
        // Find Products in DB
        $cart = Cart::find($id);
        foreach ($cart->userCarts as $userCart)
        {
            $arr[] = $userCart->cartDetails->toArray();
        }
        $cartDetails = array_reduce($arr, 'array_merge', array());
        $ids = Arr::pluck($cartDetails,'shop_product_id');
//        dd($ids);
        $products = ShopProduct::whereIn('id',$ids)->get();
//        dd($cartDetails);
        $products = $products->map(function ($item) use ($cartDetails){
            $quantity = 0;  // Set Stock Quantity
            $price = $item->price;  // Set Stock price
            foreach ($cartDetails as $cartDetail) {
                if ($item->id == $cartDetail['shop_product_id']) {
                    if ($cartDetail['bonus'])
                        $price = 0;
                    // Set new Product quantity if it less in the stock
                    $eachQuantity = min($item->quantity, $cartDetail['quantity']);
                    $quantity += $eachQuantity;

                }
            }
            // Get Product Price Tax minus discount
            $tax = (($price - $item->actualDiscount) / 100) * ($item->tax ?? 0);
            // Get Product Price without Tax for Order Total

            $priceWithoutTax = ($price - $item->actualDiscount) * $quantity;
            // Get Product Shop Tax amount
            $shopTax = ($priceWithoutTax / 100 * ($item->shop->tax ?? 0));
            // Get Total Product Price with Tax, Discount and Quantity
            $totalPrice = (($price - $item->actualDiscount) + $tax) * $quantity;

            return [
                'id' => (int)$item->id,
                'price' => round($price, 2),
                'qty' => (int)$quantity,
                'tax' => round(($tax * $quantity), 2),
                'shop_tax' => round($shopTax, 2),
                'discount' => round(($item->actualDiscount * $quantity), 2),
                'price_without_tax' => round($priceWithoutTax, 2),
                'total_price' => round($totalPrice, 2),
                'product' => $item->product->unit->translation,
                'product' => $item->product->translation,
                'product' => $item->product,
            ];
        });
        $orderTotal = round($products->sum('price_without_tax') + $products->sum('tax') + $products->sum('shop_tax'), 2);
        $bonusShop = BonusShop::with(['shopProduct.product.translation' => fn($q) => $q->actualTranslation($this->lang)->select('id','product_id','locale','title')])->where('order_amount','<=',$orderTotal)->where('shop_id',$cart->shop_id)->orderBy('order_amount','desc')->first();
        return [
            'products' => $products,
            'product_tax' => $products->sum('tax'),
            'product_total' => round($products->sum('price_without_tax'), 2),
            'order_tax' => round($products->sum('shop_tax'), 2),
            'order_total' => round($products->sum('price_without_tax') + $products->sum('tax') + $products->sum('shop_tax'), 2),
            'total_discount' => $products->sum('discount'),
            'bonus_shop' => $bonusShop
        ];
    }

    public function productsCalculate($array)
    {
        // Get Product ID from Request
        $id = collect($array['products'])->pluck('id');

        $rate = Currency::find($array['currency_id'])->rate;
        /**
         * variables для автокоплита
         * @var ShopProduct $products
         */
        $products = ShopProduct::find($id);

        $array['rate'] = $rate;

        $products = $products->map(function ($item) use ($array) {
            $quantity = $item->quantity;  // Set Stock Quantity
            $price = $item->price / $array['rate'];  // Set Stock price
            foreach ($array['products'] as $product) {
                if ($item->id == $product['id']) {
                    // Set new Product quantity if it less in the stock
                    $quantity = min($item->quantity, $product['quantity']);
                }
            }
            // Get actual discount
            $discount = $item->actual_discount / $array['rate'];
            // Get Product Price Tax minus discount
            $tax = (($price - $discount) / 100) * ($item->tax ?? 0);
            // Get Product Price without Tax for Order Total
            $priceWithoutTax = ($price - $discount) * $quantity;
            // Get Product Shop Tax amount
            $shopTax = ($priceWithoutTax / 100 * ($item->shop->tax ?? 0));

            // Get Total Product Price with Tax, Discount and Quantity
            $totalPrice = (($price - $discount) + $tax) * $quantity;

            return [
                'id' => (int) $item->id,
                'price' => round($price, 2),
                'qty' => (int) $quantity,
                'tax' => round(($tax * $quantity), 2),
                'shop_tax' => round($shopTax, 2),
                'discount' => round(($discount * $quantity), 2),
                'price_without_tax' => round($priceWithoutTax, 2),
                'total_price' => round($totalPrice, 2),
            ];
        });

        return [
            'products' =>  $products,
            'product_tax' =>  $products->sum('tax'),
            'product_total' =>  round($products->sum('price_without_tax'), 2),
            'order_tax' =>  round($products->sum('shop_tax'), 2),
            'order_total' =>  round($products->sum('price_without_tax') + $products->sum('tax') + $products->sum('shop_tax'), 2)
        ];
    }
}
