<?php

namespace App\Repositories\CategoryRepository;

use App\Models\Category;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\CategoryRepoInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CategoryRepository extends CoreRepository implements CategoryRepoInterface
{
    private $lang;

    /**
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass(): string
    {
        return Category::class;
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function parentCategories($perPage, $active = null, array $array = [])
    {
        return $this->model()->updatedDate($this->updatedDate)
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->filter($array)
            ->where('parent_id', null)
            ->with([
                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->lang),
                'children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->lang),
                'children.children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->lang)
            ])
            ->when(isset($array['shop_id']), function ($q) use ($array) {
                $q->whereHas('shopCategory', function ($query) use ($array) {
                    $query->where('shop_id', $array['shop_id']);
                });
            })
            ->when(isset($active), function ($q) use ($active) {
                $q->where('active', $active);
            })->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Get Parent, only categories where parent_id == 0
     */
    public function selectPaginate($perPage, $active = null, array $array = [])
    {
        return $this->model()
            ->select([
                'id',
                'uuid',
                'parent_id',
                'active'
            ])
            ->updatedDate($this->updatedDate)
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->filter($array)
            ->where('parent_id', null)
            ->with([
                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->lang),
                'children' => fn ($q) => $q->select(['id', 'uuid', 'parent_id', 'active']),
                'children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->lang),
                'children.children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->where('locale', $this->lang)
            ])
            ->when(isset($array['shop_id']), function ($q) use ($array) {
                $q->whereHas('shopCategory', function ($query) use ($array) {
                    $query->where('shop_id', $array['shop_id']);
                });
            })
            ->when(isset($active), function ($q) use ($active) {
                $q->where('active', $active);
            })->orderByDesc('id')->paginate($perPage);
    }

    /**
     * Get categories with pagination
     */
    public function categoriesPaginate($perPage = 15, $active = null, $array = [])
    {
        return $this->model()->updatedDate($this->updatedDate)
            ->with([
                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->actualTranslation($this->lang),
                'children.translation' => fn($q) => $q->actualTranslation($this->lang),
            ])
            ->when(isset($active), function ($q) use ($active) {
                $q->whereActive($active);
            })
            ->paginate($perPage);
    }

    /**
     * Get all categories list
     */
    public function categoriesList($array = [])
    {
        return $this->model()->updatedDate($this->updatedDate)->with([
            'parent.translation' => fn($q) => $q->actualTranslation($this->lang),
            'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', $this->lang)
        ])->orderByDesc('id')->get();
    }

    /**
     * Get one category by Identification number
     */
    public function categoryDetails(int $id)
    {
        return $this->model()->with([
            'parent.translation' => fn($q) => $q->actualTranslation($this->lang),
            'translation' => fn($q) => $q->actualTranslation($this->lang)
        ])->find($id);
    }

    /**
     * Get one category by slug
     */
    public function categoryByUuid($uuid)
    {
        return $this->model()->where('uuid', $uuid)->withCount('products')
            ->with([
                'translation' => fn($q) => $q->actualTranslation($this->lang),
                'children.translation' => fn($q) => $q->actualTranslation($this->lang)
            ])->first();
    }

    public function categoriesSearch(string $search, $active = null)
    {
        return $this->model()->with([
            'translation' => fn($q) => $q->actualTranslation($this->lang)
        ])
            ->where(function ($query) use ($search) {
                $query->where('keywords', 'LIKE', '%' . $search . '%');
            })
            ->orWhereHas('translations', function ($q) use ($search) {
                $q->where('title', 'LIKE', '%' . $search . '%')
                    ->select('id', 'category_id', 'locale', 'title');
            })
            ->when(isset($active), function ($q) use ($active) {
                $q->whereActive($active);
            })
            ->latest()->take(50)->get();
    }

    public function shopCategory($perPage = null, int $shop_id)
    {
        $perPage = $perPage ?? 10;
        return $this->model()->with([
            'translation' => fn($q) => $q->actualTranslation($this->lang),
            'children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', $this->lang),
            'children.children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                ->where('locale', $this->lang)
            ])
            ->whereHas('shopCategory', function ($q) use ($shop_id) {
            $q->where('shop_id', $shop_id);
        })
            ->orderByDesc('id')->paginate($perPage);
    }

    public function shopCategoryPaginate($perPage = 10)
    {
        return $this->model()->with(['translation' => fn($q) => $q->actualTranslation($this->lang)])->orderByDesc('id')->paginate($perPage);
    }

    public function shopCategoryById(int $id, int $shop_id)
    {
        return $this->model()->with('translations')->whereHas('shopCategory', function ($q) use ($shop_id) {
            $q->where('shop_id', $shop_id);
        })->orderByDesc('id')->find($id);
    }

    public function parentCategory()
    {
        return $this->model()->with(['translation' => fn($q) => $q->actualTranslation($this->lang)])->ordeByDesc('id')->get();
    }

    //Bu methodda shop o`ziga tortib olmagan categorylar chiqadi

    public function shopCategoryNonExistPaginate(int $shop_id, $array, $perPage)
    {
        $shopCategoryIds = $this->model()->whereHas('shopCategory', function ($q) use ($shop_id) {
            $q->where('shop_id', $shop_id);
        })->pluck('id');

        return $this->model()->with(['translation' => fn($q) => $q->actualTranslation($this->lang)])->whereNotIn('id', $shopCategoryIds)->where('active', 1)->filter($array)->orderByDesc('id')->paginate($perPage);
    }

    // Bu methodda shop o`ziga tegishli bo`lgan category productlarni oladi.

    /** @todo Проверить скорость работы говно метода */
    public function shopCategoryProduct($shopCategoryIds, $array, $perPage): LengthAwarePaginator
    {
        /** @var Category $categories */
        $categories = $this->model();

        $page = data_get($array, 'page') ?: Paginator::resolveCurrentPage('links');

        $perPage = $perPage ?: $categories->getPerPage();

        $categories = $categories
            ->with([
                'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')
                    ->actualTranslation($this->lang),
            ])
            ->whereIn('id', $shopCategoryIds)
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get()
            ->map(function ($category) use ($array) {
                $shopProduct = $category->shopProduct->when(data_get($array, 'shop_id'), function ($query, $shopId) {
                    return $query->where('shop_id', $shopId);
                })
                    ->loadAvg('reviews','rating')
                    ->load([
                    'product.translation' => fn($q) => $q->actualTranslation($this->lang)->select('id', 'locale', 'title', 'product_id'),
                    'product.unit.translation' => fn($q) => $q->actualTranslation($this->lang)->select('id', 'locale', 'title', 'unit_id'),
                    'bonus' => fn($q) => $q->select('id', 'shop_product_id'),
                ])->take(10);

                if (count($shopProduct) > 0) {
                    return $category->setRelation('shopProduct', $shopProduct);
                }
                return false;
            })
            ->reject(function ($value) {
                return empty($value->shopProduct);
            });
        return new LengthAwarePaginator(
            $categories,
            $categories->count(),
            $perPage,
            data_get($array, 'page', 1),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => 'links',
            ]
        );
    }

    public function childrenCategory($perPage, int $id)
    {
        return $this->model()->with([
            'translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')->actualTranslation($this->lang),
            'children.translation' => fn($q) => $q->select('id', 'locale', 'title', 'category_id')->actualTranslation($this->lang)
        ])->where('parent_id', null)->where('id', $id)->paginate($perPage);
    }

}
