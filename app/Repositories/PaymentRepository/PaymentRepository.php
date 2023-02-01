<?php

namespace App\Repositories\PaymentRepository;

use App\Models\Payment;
use App\Repositories\CoreRepository;
use App\Repositories\Interfaces\PaymentRepoInterface;

class PaymentRepository extends CoreRepository implements PaymentRepoInterface
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
        return Payment::class;
    }

    public function paymentsList($array)
    {
        return $this->model()
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang)
            ])
            ->when(isset($array['active']), function ($q) use($array) {
                $q->where('active', $array['active']);
            })
            ->get();
    }

    public function paginate($perPage, $array)
    {
        return $this->model()
            ->with([
                'translation' => fn($q) => $q->where('locale', $this->lang),
                'translations',
            ])
            ->when(isset($array['active']), function ($q) use($array) {
                $q->where('active', $array['active']);
            })
            ->paginate($perPage);
    }

    public function paymentDetails(int $id)
    {
        return $this->model()->with([
                'translation' => fn($q) => $q->where('locale', $this->lang)
            ])
            ->find($id);
    }

    public function shopPaymentNonExistPaginate(int $shop_id,int $perPage)
    {
        $paymentIds = $this->model()->whereHas('shopPayment', function ($q) use ($shop_id){
           $q->where('shop_id',$shop_id);
        })->pluck('id');
        return $this->model()->whereNotIn('id',$paymentIds)->where('active',1)->orderByDesc('id')->paginate($perPage);
    }
}
