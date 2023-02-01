<?php

namespace App\Http\Controllers\API\v1\Dashboard\Seller;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ShopProductResource;
use App\Http\Resources\UserResource;
use App\Repositories\DashboardRepository\DashboardRepository;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends SellerBaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function countStatistics(Request $request)
    {
        $result = (new DashboardRepository())->statisticCount($request->merge(['shop_id' => $this->shop->id])->all());
        return $this->successResponse(__('web.statistics_count'), $result);

    }

    public function sumStatistics(Request $request)
    {
        $result = (new DashboardRepository())->statisticSum($request->merge(['shop_id' => $this->shop->id])->all());
        return $this->successResponse(__('web.statistics_sum'), $result);

    }

    public function topCustomersStatistics(Request $request)
    {
        $result = (new DashboardRepository())->statisticTopCustomer($request->merge(['shop_id' => $this->shop->id])->all());
        return $this->successResponse(__('web.statistics_top_customer'), UserResource::collection($result));

    }

    public function topProductsStatistics(Request $request)
    {
        $result = (new DashboardRepository())->statisticTopSoldProducts($request->merge(['shop_id' => $this->shop->id])->all());
        return $this->successResponse(__('web.statistics_top_products'), ShopProductResource::collection($result));

    }

    public function ordersSalesStatistics(Request $request)
    {
        $result = (new DashboardRepository())->statisticOrdersSales($request->merge(['shop_id' => $this->shop->id])->all());
        return $this->successResponse(__('web.statistics_orders_sales'), $result);

    }

    public function ordersCountStatistics(Request $request)
    {
        $result = (new DashboardRepository())->statisticOrdersCount($request->merge(['shop_id' => $this->shop->id])->all());
        return $this->successResponse(__('web.statistics_order_count'), $result);

    }
}
