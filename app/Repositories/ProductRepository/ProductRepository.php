<?php

namespace App\Repositories\ProductRepository;

use App\Models\Product;
use App\Models\ShopProduct;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\ProductRepoInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository extends CoreRepository implements ProductRepoInterface
{
    private string $lang;

    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass()
    {
        return Product::class;
    }

    public function productsList($active = null, $array = [])
    {
        return $this->model()->whereHas('translation', function ($q) {
            $q->where('locale', $this->lang);
        })
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang)
            ])
            ->updatedDate($this->updatedDate)
            ->filter($array)
            ->when(isset($active), function ($q) use ($active) {
                $q->whereActive($active);
            })->get();
    }

    public function productsPaginate($perPage, $active = null, $array = [])
    {
        return $this->model()->filter($array)->updatedDate($this->updatedDate)
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->withAvg('reviews', 'rating')
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'product_id', 'locale', 'title'),
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($q) => $q->where('locale', $this->lang),
            ])
            ->when(isset($array['search']), function ($q) use ($array) {
                $q->where(function ($query) use ($array) {
                    $query->where('keywords', 'LIKE', '%' . $array['search'] . '%');
                })->orWhereHas('translations', function ($q) use ($array) {
                    $q->where('title', 'LIKE', '%' . $array['search'] . '%')
                        ->select('id', 'product_id', 'locale', 'title');
                });
            })
            ->orderBy($array['column'] ?? 'id', $array['sort'] ?? 'desc')
            ->paginate($perPage);
    }

    public function productDetails(int $id)
    {
        return $this->model()
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->withAvg('reviews', 'rating')
            ->with([
                'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title'),
                'translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'product_id', 'locale', 'title'),
                'category.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($q) => $q->where('locale', $this->lang),

            ])->find($id);
    }

    public function productByUUID(string $uuid)
    {
        return $this->model()
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with([
                'galleries' => fn($q) => $q->select('id', 'type', 'loadable_id', 'path', 'title'),
                'properties' => fn($q) => $q->where('locale', $this->lang),
//            'shop.translation' => fn($q) => $q->where('locale', $this->lang),
                'category' => fn($q) => $q->select('id', 'uuid'),
                'category.translation' => fn($q) => $q->where('locale', $this->lang)
                    ->select('id', 'category_id', 'locale', 'title'),
                'brand' => fn($q) => $q->select('id', 'uuid', 'title'),
                'unit.translation' => fn($q) => $q->where('locale', $this->lang),
                'reviews.galleries',
                'reviews.user',
                'translation' => fn($q) => $q->where('locale', $this->lang),
                'translations',
            ])

//            ->whereHas('shop', function ($item) {
//                $item->whereNull('deleted_at');
//            })
            ->firstWhere('uuid', $uuid);
    }

    public function productsByIDs(array $ids)
    {
        return $this->model()->with([
            'translation' => fn($q) => $q->where('locale', $this->lang)
                ->select('id', 'product_id', 'locale', 'title'),
        ])
            ->orderBy($array['column'] ?? 'id', $array['sort'] ?? 'desc')
            ->find($ids);
    }

    public function productsSearch($perPage, $active = null, $array = []): LengthAwarePaginator
    {

        /** @var Product $model */
        $model = $this->model();

        return $model->filter($array)->updatedDate($this->updatedDate)
            ->with([
                'translation' => fn($q) => $q->select(['id', 'product_id', 'locale', 'title'])
                    ->when(data_get($array, 'lang'), fn($q, $lang) => $q->where('locale', $lang)),
                'unit' => fn($q) => $q->select(['id', 'active', 'position']),
                'unit.translation' => fn($query) => $query
                    ->when(data_get($array, 'lang'), fn($q, $lang) => $q->where('locale', $lang)),
            ])
            ->when(data_get($array, 'search'), function ($q, $search) {
                /** @var Product $q */
                $q->where('keywords', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->select('id', 'product_id', 'locale', 'title');
                });

            })
            ->when(data_get($array, 'shop_id'), function ($q, $shopId) {
                $q->where('shop_id', $shopId);
            })
            ->orderBy(data_get($array, 'column', 'id'), data_get($array, 'sort', 'desc'))
            ->paginate($perPage);
    }

    public function shopProductsSearch($perPage, $active = null, $array = []): LengthAwarePaginator
    {

        $model = clone new ShopProduct;

        return $model->where('shop_id', data_get($array, 'shop.id'))
            ->filter($array)->updatedDate($this->updatedDate)
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
            ->orderBy(data_get($array, 'column', 'id'), data_get($array, 'sort', 'desc'))
            ->paginate($perPage);
    }

    //Bu methodda shop o`ziga tortib olmagan productlar chiqadi

    public function shopProductNonExistPaginate(int $shop_id, $array, $perPage)
    {
        $shopProductIds = $this->model()->whereHas('shopProduct', function ($q) use ($shop_id) {
            $q->where('shop_id', $shop_id);
        })->pluck('id');
        return $this->model()->whereHas('translation', function ($q) {
            $q->actualTranslation($this->lang);
        })->with(['translation' => fn($q) => $q->actualTranslation($this->lang)])->whereNotIn('id', $shopProductIds)->filter($array)->orderByDesc('id')->paginate($perPage);
    }
}

