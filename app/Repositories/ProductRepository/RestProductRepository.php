<?php

namespace App\Repositories\ProductRepository;

use App\Models\Category;
use App\Models\Product;
use App\Models\ShopProduct;
use App\Repositories\CoreRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class RestProductRepository extends CoreRepository
{

    private string $lang;
    private array $ids = [];

    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass(): string
    {
        return ShopProduct::class;
    }

    public function findSecondRecursive($categories)
    {
        foreach ($categories as $category) {
            /** @var Category $category */
            $category = $category->load([
                'children'
            ]);

            $this->ids[] = $category->id;

            if (!empty($category->children) && count($category->children) > 0) {
                $this->ids[] = $category->children->pluck('id')->toArray();
                $this->findOneRecursive($category->children);
            }

        }
    }

    public function findOneRecursive($categories)
    {
        foreach ($categories as $category) {
            /** @var Category $category */
            $category = $category->load([
                'children'
            ]);

            $this->ids[] = $category->id;

            if (!empty($category->children) && count($category->children) > 0) {
                $this->ids[] = $category->children->pluck('id')->toArray();
                $this->findSecondRecursive($category->children);
            }

        }
    }

    public function prepareCategoryIds($categoryId) {

        /** @var Category $category */
        $category = Category::with([
            'children' => fn($q) => $q->select(['id', 'parent_id']),
        ])
            ->select(['id'])
            ->find($categoryId);



        $this->ids[] = $category->id;

        $this->findOneRecursive($category->children);

        $collectIds = [];

        foreach ($this->ids as $id) {

            if (is_array($id)) {
                $collectIds = array_merge($collectIds, $id);
            } else {
                $collectIds[] = $id;
            }

        }
        $this->ids = $collectIds;
    }

    public function productsPaginate($perPage, $active = null, $array = [])
    {
        $categoryId = data_get($array, 'category_id');

        if (!empty($categoryId)) {

            $this->prepareCategoryIds($categoryId);

            unset($array['category_id']);

            data_set($array, 'category_ids', array_unique($this->ids));
        }


        return $this->model()->filter($array)->updatedDate($this->updatedDate)
            ->whereHas('product.translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->withAvg('reviews', 'rating')
            ->with([
                'product.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'product_id', 'locale', 'title'),
                'product.category' => fn($q) => $q->select('id', 'uuid'),
                'product.category.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'category_id', 'locale', 'title'),
                'product.brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'product.unit.translation' => fn($q) => $q->where('locale', $this->lang),
                'shop.translation' => fn($q) => $q->where('locale', $this->lang),
                'discount',
                'bonus',
            ])
            ->when(isset($array['search']), function ($q) use ($array) {
                $q->whereHas('product', function ($query) use ($array) {
                    $query->where('keywords', 'LIKE', '%' . $array['search'] . '%');
                })->orWhereHas('product.translations', function ($q) use ($array) {
                    $q->where('title', 'LIKE', '%' . $array['search'] . '%')
                        ->select('id', 'product_id', 'locale', 'title');
                });
            })
            ->when(isset($array['shop_id']), function ($q) use ($array) {
                $q->where('shop_id', $array['shop_id']);
            })
            ->when(isset($active), function ($q) use ($active) {
                $q->where('active', $active);
            })
            ->orderBy($array['column'] ?? 'id', $array['sort'] ?? 'desc')
            ->paginate($perPage);
    }

    public function productsPaginateSearch($perPage, $active = null, $array = []): LengthAwarePaginator
    {
        /** @var ShopProduct $model */
        $model = $this->model();

        return $model->filter($array)->updatedDate($this->updatedDate)
            ->withProductTranslations(['lang' => $this->lang])
            ->when(data_get($array, 'search'), function ($q, $search) {
                /** @var Product $q */
                $q->whereHas('product', function ($query) use($search) {
                    $query->where('keywords', 'LIKE', '%' . $search . '%');
                })->orWhereHas('product.translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->select('id', 'product_id', 'locale', 'title');
                });

            })
            ->when(data_get($array, 'shop_id'), function ($q, $shopId) {
                $q->where('shop_id', $shopId);
            })
            ->when(isset($active), function ($q, $active) {
                $q->where('active', $active);
            })
            ->orderBy(data_get($array, 'column', 'id'), data_get($array, 'sort', 'desc'))
            ->paginate($perPage);
    }

    public function productByUUID(string $uuid)
    {
        return $this->model()
            ->whereHas('product.translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with([
                'bonus.bonusProduct.product.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'product_id', 'locale', 'title'),
                'product.galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title'),
                'product.properties' => fn($q) => $q->where('locale', $this->lang),
                'shop.translation' => fn($q) => $q->where('locale', $this->lang),
                'product.category' => fn($q) => $q->select('id', 'uuid'),
                'product.category.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'category_id', 'locale', 'title'),
                'product.brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'product.unit.translation' => fn($q) => $q->where('locale', $this->lang),
                'reviews.galleries',
                'reviews.user',
                'product.translation' => fn($q) => $q->where('locale', $this->lang),
                'discount',
                'product.translations',
            ])
//            ->whereHas('shop', function ($item) {
//                $item->whereNull('deleted_at');
//            })
            ->firstWhere('uuid', $uuid);
    }

    public function productsMostSold($perPage, $array = [])
    {
        return $this->model()->filter($array)->updatedDate($this->updatedDate)
            ->withAvg('reviews', 'rating')
            ->withCount('orders')
            ->with([
                'product.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'product_id', 'locale', 'title'),
                'bonus' => fn($q) => $q->select('shop_product_id', 'id')

            ])
            ->whereActive(1)
            ->when(isset($array['shop_id']), function ($q) use ($array) {
                $q->where('shop_id', $array['shop_id']);
            })
            ->orderBy('orders_count', 'desc')
            ->take(10)
            ->paginate($perPage);
    }

    /**
     * @return array|Application|Request|string|null
     */
    public function productsDiscount($perPage, $array = [])
    {
        return $this->model()->filter($array)->updatedDate($this->updatedDate)
            ->whereHas('discount')
            ->whereHas('product.translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->withAvg('reviews', 'rating')
            ->with([
                'product.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'product_id', 'locale', 'title'),
                'bonus' => fn($q) => $q->select('shop_product_id', 'id')

            ])->when(isset($array['shop_id']), function ($q) use ($array) {
                $q->where('shop_id', $array['shop_id']);
            })
            ->whereActive(1)
            ->paginate($perPage);
    }

    public function productsByIDs(array $ids)
    {
        return $this->model()->with([
            'product.translation' => fn($q) => $q->where('locale', $this->lang)
                ->select('id', 'product_id', 'locale', 'title'),
            'product.unit.translation' => fn($q) => $q->where('locale', $this->lang),
            'discount'
        ])
            ->withAvg('reviews', 'rating')
            ->orderBy($array['column'] ?? 'id', $array['sort'] ?? 'desc')
            ->find($ids);
    }
}
