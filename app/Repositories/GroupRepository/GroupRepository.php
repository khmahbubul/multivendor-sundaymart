<?php

namespace App\Repositories\GroupRepository;

use App\Models\Group;
use App\Repositories\CoreRepository;

class GroupRepository extends CoreRepository
{
    private $lang;

    /**
     * @param $lang
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang = $this->setLanguage();
    }

    protected function getModelClass()
    {
        return Group::class;
    }

    public function paginate($perPage,$active = null)
    {
        return $this->model()
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang)
            ])
            ->when(isset($active), function ($q) {
                $q->where('status', true);
            })
            ->paginate($perPage);
    }

    public function show(int $id)
    {
        return $this->model()->with([
            'translation' => fn($q) => $q->where('locale', $this->lang),
            'translations'
        ])
            ->find($id);
    }


}
