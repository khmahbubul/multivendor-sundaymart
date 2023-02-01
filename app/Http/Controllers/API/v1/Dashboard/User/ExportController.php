<?php

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Models\Order;
use App\Models\Translation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use function request;

class ExportController extends UserBaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function orderExportPDF(int $id)
    {
//        $file = Storage::disk('public')->get('admin.json');
//
//        $files = json_decode($file);
//        foreach ($files as $index => $file) {
//            Translation::updateOrCreate(['key' => $index, 'locale' => 'en'],[
//                'status' => 1,
//                'value' => $file,
//                'group' => 'web',
//            ]);
//        }
//
//        return $this->successResponse(trans('web.file_successfully_export', [], request()->lang ?? config('app.locale')), []);

        $order = Order::with('orderDetails.orderStocks', 'orderDetails.shop')->find($id);
        if ($order) {
            $pdf = PDF::loadView('order-invoice', compact('order'));
            $pdf->save(Storage::disk('public')->path('export/invoices') . '/order_invoice.pdf');

            return response(Storage::disk('public')->get('/export/invoices/order_invoice.pdf'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment',
            ]);
        }
        return $this->errorResponse(ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang ?? config('app.locale')));
    }

}
