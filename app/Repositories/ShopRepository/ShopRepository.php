<?php

namespace App\Repositories\ShopRepository;

use App\Models\Shop;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\ShopRepoInterface;

class ShopRepository extends CoreRepository implements ShopRepoInterface
{
    private $lang;

    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass()
    {
        return Shop::class;
    }

    /**
     * Get all Shops from table
     */
    public function shopsList(array $array = []) {
        return $this->model()->updatedDate($this->updatedDate)
            ->filter($array)
            ->with([
                'translation' => fn($q) => $q->actualTranslation($this->lang),
                'seller.roles',
                'seller' => function($q) {
                    $q->select('id', 'firstname', 'lastname');
                }
            ])->orderByDesc('id')->orderByDesc('updated_at')->get();
    }

    /**
     * Get one Shop by UUID
     * @param int $perPage
     * @param array $array
     * @return mixed
     */
    public function shopsPaginate(int $perPage, array $array = [])
    {
        return $this->model()->updatedDate($this->updatedDate)
            ->withAvg('reviews', 'rating')
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->filter($array)
            ->with([
                'translation' => function($q) {
                    $q->where('locale', $this->lang)
                        ->select('id', 'locale', 'title','description', 'shop_id','address');
                },
                'seller.roles',
                'seller' => function($q) {
                    $q->select('id', 'firstname', 'lastname');
                },
            ])
            ->orderBy($array['column'] ?? 'id', $array['sort'] ?? 'desc')
            ->paginate($perPage);
    }

    /**
     * @param string $uuid
     * @return mixed
     */
    public function shopDetails(string $uuid)
    {
        return $this->model()->query()
            ->withAvg('reviews', 'rating')
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang),
                'subscription',
                'seller.roles',
                'shopPayments.payment',
                'deliveries.translation' => fn($q) => $q->actualTranslation($this->lang),
                'deliveries.translations',
                'seller' => function($q) {
                    $q->select('id', 'firstname', 'lastname');
                }
            ])->firstWhere('uuid', $uuid);
    }

    /**
     * @param string $uuid
     * @return mixed
     */
    public function shopById(int $id)
    {
        return $this->model()->query()
            ->withAvg('reviews', 'rating')
            ->whereHas('translation', function ($q) {
                $q->where('locale', $this->lang);
            })
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang),
                'subscription',
                'seller.roles',
                'seller' => function($q) {
                    $q->select('id', 'firstname', 'lastname');
                }
            ])->find($id);
    }

    /**
     * @param string $search
     * @param array $array
     * @return mixed
     */
    public function shopsSearch(string $search, $array = [])
    {
        return $this->model()->with([
            'translation' => fn($q) => $q->actualTranslation($this->lang)
        ])
            ->where('phone', 'LIKE', '%'. $search . '%')
            ->orWhereHas('translations', function ($q) use($search) {
                $q->where('title', 'LIKE', '%'. $search . '%')
                    ->select('id', 'shop_id', 'locale', 'title');
            })
            ->filter($array)
            ->latest()->take(10)->get();
    }

    /**
     * @param array $ids
     * @param null $status
     * @return mixed
     */
    public function shopsByIDs(array $ids = [], $status = null)
    {
        return $this->model()->with([
            'translation' => fn($q) => $q->actualTranslation($this->lang),
            'deliveries.translation' => fn($q) => $q->actualTranslation($this->lang),
        ])
            ->when(isset($status), function ($q) use ($status) {
                $q->where('status', $status);
            })->find($ids);
    }

}
