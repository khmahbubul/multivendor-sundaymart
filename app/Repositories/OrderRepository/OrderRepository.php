<?php

namespace App\Repositories\OrderRepository;

use App\Helpers\ResponseError;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\OrderDetailResource;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\OrderRepoInterface;
use App\Services\OrderService\OrderDetailService;
use Symfony\Component\HttpFoundation\Response;

class OrderRepository extends CoreRepository implements OrderRepoInterface
{
    private $lang;

    /**
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }


    protected function getModelClass()
    {
        return Order::class;
    }

    public function ordersList(array $array = [])
    {
        return $this->model()->with('orderDetails.products')
            ->updatedDate($this->updatedDate)
            ->filter($array)->get();
    }

    public function ordersPaginate(int $perPage, int $userId = null, array $array = [])
    {
        return $this->model()->withCount('orderDetails')
            ->with([
                'user',
                'bonusShop',
                'deliveryMan' => fn($q) => $q->select('id','firstname','lastname','phone'),
                'transaction.paymentSystem.payment.translation' => fn($q) => $q->actualTranslation($this->lang),
                'shop.translation' => fn($q) => $q->actualTranslation($this->lang),
                'currency' => function ($q) {
                    $q->select('id', 'title', 'symbol');
                }])

            ->when(isset($array['search']), function ($q) use ($array) {
                $q->where('id',  'LIKE', '%'. $array['search'] . '%')
                ->orWhere('price',  'LIKE', '%'. $array['search'] . '%')
                ->orWhere('note',  'LIKE', '%'. $array['search'] . '%');
            })
            ->when(isset($array['shop_id']), function ($q) use ($array) {
                $q->where('shop_id', $array['shop_id'])->with('shop');
            })
            ->updatedDate($this->updatedDate)
            ->filter($array)
            ->when(isset($userId), function ($q) use($userId) {
                $q->where('user_id', $userId);
            })
            ->orderBy($array['column'] ?? 'id', $array['sort'] ?? 'desc')->paginate($perPage);
    }

    public function show(int $id, $shopId = null)
    {
        info('SHOP', [$shopId]);
        return $this->model()
            ->with([
                'user',
                'currency' => function ($q) {
                    $q->select('id', 'title', 'symbol');
                },
                'deliveryType.translation' => fn($q) => $q->actualTranslation($this->lang),
                'deliveryAddress',
                'deliveryMan',
                'coupon',
                'shop.translation' => fn($q) => $q->actualTranslation($this->lang),
                'transaction.paymentSystem.payment' => function ($q) {
                    $q->select('id', 'tag', 'active');
                },
                'transaction.paymentSystem.payment.translation' => function ($q) {
                    $q->select('id', 'locale', 'payment_id', 'title')->actualTranslation($this->lang);
                },
                'orderDetails.shopProduct.product.translation' => function ($q) {
                    $q->select('id', 'locale','product_id', 'title')->actualTranslation($this->lang);
                },
                'orderDetails.shopProduct.product.unit.translation' => function ($q) {
                    $q->select('id', 'locale','unit_id', 'title')->actualTranslation($this->lang);
                },
                'orderDetails.shopProduct.discount',
                'reviews',
                'bonusShop.shopProduct.product.translation' => function ($q) {
                    $q->select('id', 'locale','product_id', 'title')->actualTranslation($this->lang);
                },
            ])
            ->when(isset($shopId), function ($q) use ($shopId) {
                $q->where('shop_id', $shopId)->with('shop');
            })
            ->find($id);
    }

    public function getById(int $id)
    {
        return $this->model()->find($id);
    }
}
