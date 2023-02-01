<?php

namespace App\Repositories\BranchRepository;


use App\Models\Branch;
use App\Repositories\CoreRepository;

class BranchRepository extends CoreRepository
{
    private $lang;
    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass()
    {
        return Branch::class;
    }


    public function paginate($perPage, $shop = null)
    {
        return $this->model()->whereHas('translation', function ($q) {
            $q->where('locale', $this->lang);
        })
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang)
            ])
            ->when(isset($shop), function ($q) use ($shop) {
                $q->where('shop_id', $shop);
            })
            ->paginate($perPage);
    }


    public function getById(int $id, $shop = null)
    {
        return $this->model()->with([
            'translations',
            'translation' => function($q) {
                $q->actualTranslation($this->lang);
            }
        ])
            ->when(isset($shop), function ($q) use($shop) {
                $q->where('shop_id', $shop);
            })->find($id);
    }
}
