<?php

namespace App\Repositories\DashboardRepository;

use App\Models\Order;
use App\Models\Review;
use App\Models\ShopProduct;
use App\Models\User;
use App\Repositories\CoreRepository;
use Illuminate\Support\Facades\DB;

class DashboardRepository extends CoreRepository
{

    protected function getModelClass()
    {
        return Order::class;
    }

    public function statisticCount($array = [])
    {
        // GET ORDERS COUN
        $order = DB::table('orders')
            ->select(DB::raw("sum(case when (status='new') then 1 else 0 end) as count_new_orders,
            sum(case when (status='accepted') then 1 else 0 end) as count_accepted_orders,
            sum(case when (status='ready') then 1 else 0 end) as count_ready_orders,
            sum(case when (status='on_a_way') then 1 else 0 end) as count_on_a_way_orders,
            sum(case when (status='delivered') then 1 else 0 end) as count_delivered_orders,
            sum(case when (status='canceled') then 1 else 0 end) as count_canceled_orders,
            sum(price) as total_earned,
            sum(delivery_fee) as delivery_earned,
            sum(tax) as tax_earned,
            sum(commission_fee) as commission_fee
        "))->when(isset($array['shop_id']), function ($q) use ($array){
            $q->where('shop_id', $array['shop_id']);
            })->whereNull('deleted_at')->first();


        // GET PRODUCTS OUT OF STOCK COUNT
        $product = DB::table('shop_products')
            ->select(DB::raw("sum(case when (quantity = 0) then 1 else 0 end) as count_out_of_stock_product,
        count(id) as count_product
        "))->when(isset($array['shop_id']), function ($q) use ($array){
                $q->where('shop_id', $array['shop_id']);
            })->whereNull('deleted_at')->first();

        // GET REVIEWS COUNT
        $reviews = Review::with('reviewable')
            ->where('reviewable_type', Order::class)->count();
        return [
            'count_new_orders' => $order->count_new_orders,
            'count_accepted_orders' => $order->count_accepted_orders,
            'count_ready_orders' => $order->count_ready_orders,
            'count_on_a_way_orders' => $order->count_on_a_way_orders,
            'count_delivered_orders' => $order->count_delivered_orders,
            'count_canceled_orders' => $order->count_canceled_orders,
            'total_earned' => $order->total_earned,
            'delivery_earned' => $order->delivery_earned,
            'tax_earned' => $order->tax_earned,
            'commission_fee' => $order->commission_fee,
            'count_out_of_stock_product' => $product->count_out_of_stock_product,
            'count_product' => $product->count_product,
            'reviews' => $reviews,
        ];
    }

    public function statisticTopCustomer($array = [])
    {
        $time = $array['time'] ?? 'subMonth';

        return User::whereDate('created_at', '>', now()->{$time}())
            ->when(isset($array['shop_id']), function ($shop) use ($array) {
                $shop->whereHas('orders', function ($q) use ($array) {
                    $q->where('shop_id', $array['shop_id']);
                })
                    ->withSum(['orders' => function ($q) use ($array) {
                        $q->where('shop_id', $array['shop_id']);
                    }], 'price');
            }, function ($q) {
                $q->withSum('orders', 'price');
            })
            ->orderByDesc('orders_sum_price')
            ->take(5)->get();
    }

    public function statisticTopSoldProducts($array = [])
    {
        $time = $array['time'] ?? 'subMonth';

        return ShopProduct::with([
            'product.category',
            'product.translation' => function ($q) {
                $q->actualTranslation($this->setLanguage());
            }
        ])->when(isset($array['shop_id']), function ($shop) use ($array) {
            $shop->where('shop_id', $array['shop_id']);
        })
            ->withCount('orders')
            ->where('active', 1)
            ->whereDate('created_at', '>', now()->{$time}())
            ->orderByDesc('orders_count')
            ->take(5)->get();
    }

    public function statisticOrdersSales($array = [])
    {
        $time = $array['time'] ?? 'subYear';

        return $this->model()
            ->whereDate('created_at', '>', now()->{$time}())
            ->when(isset($array['shop_id']), function ($shop) use ($array) {
                $shop->where('shop_id', $array['shop_id']);
            })
            ->where('status', 'delivered')
            ->selectRaw('DATE(created_at) as date, ROUND(SUM(price), 2) as price')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
    }

    public function statisticOrdersCount($array = [])
    {
        $time = $array['time'] ?? 'subYear';

        return $this->model()
            ->whereDate('created_at', '>', now()->{$time}())
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->when(isset($array['shop_id']), function ($shop) use ($array) {
                $shop->where('shop_id', $array['shop_id']);
            })
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();
    }
}
