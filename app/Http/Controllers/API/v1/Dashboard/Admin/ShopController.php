<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Exports\BrandExport;
use App\Exports\ShopExport;
use App\Helpers\ResponseError;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Requests\ShopCreateRequest;
use App\Http\Requests\ShopStatusChangeRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Repositories\Interfaces\ShopRepoInterface;
use App\Repositories\ShopRepository\ShopDeliveryRepository;
use App\Services\Interfaces\ShopServiceInterface;
use App\Services\ShopServices\ShopActivityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ShopController extends AdminBaseController
{
    private ShopServiceInterface $shopService;
    private ShopRepoInterface $shopRepository;

    public function __construct(ShopServiceInterface $shopService, ShopRepoInterface $shopRepository)
    {
        parent::__construct();
        $this->shopService = $shopService;
        $this->shopRepository = $shopRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $shops = $this->shopRepository->shopsList($request->all());
        return $this->successResponse(__('web.shop_list'), ShopResource::collection($shops));
    }

    /**
     * Display a listing of the resource.
     */
    public function paginate(Request $request): AnonymousResourceCollection
    {
        $shops = $this->shopRepository->shopsPaginate($request->perPage ?? 15, $request->all());
        return ShopResource::collection($shops);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShopCreateRequest $request): JsonResponse
    {
        $shop = Shop::where('user_id', $request->user_id ?? auth('sanctum')->id())->first();
        if (!$shop) {
            $result = $this->shopService->create($request);
            if ($result['status']) {
                return $this->successResponse(__('web.record_successfully_created'), ShopResource::make($result['data']));
            }
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->errorResponse(
           ResponseError::ERROR_206, trans('errors.' . ResponseError::ERROR_206, [], \request()->lang ?? 'en'),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid, Request $request): JsonResponse
    {
        $shop = $this->shopRepository->shopDetails($uuid);

        if ($shop) {

            $shop->load([
                'group',
                'group.translation' => fn($q) => $q->where('locale', $request->input('lang')),
                'group.translations',
                'translation' => fn($q) => $q->where('locale', $request->input('lang')),
                'translations',
            ]);

            return $this->successResponse(__('web.shop_found'), ShopResource::make($shop));
        }

        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $uuid): JsonResponse
    {
        $shop = Shop::where(['user_id' => $request->user_id, 'uuid' => $uuid])->first();
        if ($shop) {
            $result = $this->shopService->update($uuid, $request);
            if ($result['status']) {
                return $this->successResponse(__('web.record_successfully_updated'), ShopResource::make($result['data']));
            }
            return $this->errorResponse(
                $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
                Response::HTTP_BAD_REQUEST
            );
        }
        return $this->errorResponse(
            ResponseError::ERROR_207, trans('errors.' . ResponseError::ERROR_207, [], \request()->lang ?? 'en'),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteAllRequest $request): JsonResponse
    {
        $collection = $request->validated();

        $result = $this->shopService->delete($collection['ids']);
        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Search shop Model from database.
     *
     * @return AnonymousResourceCollection
     */
    public function shopsSearch(Request $request)
    {
        $categories = $this->shopRepository->shopsSearch($request->search ?? '');
        return ShopResource::collection($categories);
    }

    /**
     * Remove Model image from storage.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function imageDelete(string $uuid)
    {
        $validator = Validator::make(\request()->all(), [
            'tag' => ['required',Rule::in('background','logo')]
        ]);
        if ($validator->fails()) {
            return $this->errorResponse(
                ResponseError::ERROR_400, $validator->errors()->first(),
                Response::HTTP_BAD_REQUEST
            );
        }
        $tag = request()->tag;
        $shop = Shop::firstWhere('uuid', $uuid);
        if ($shop) {
            Storage::disk('public')->delete($shop->img);
            $shop->update([$tag . '_img' => null]);

            return $this->successResponse(__('web.image_has_been_successfully_delete'), $shop);
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Change Shop Status.
     *
     * @param string $uuid
     * @param ShopStatusChangeRequest $request
     * @return JsonResponse
     */
    public function statusChange(string $uuid, ShopStatusChangeRequest $request){
        $result = (new ShopActivityService())->changeStatus($uuid, $request->status);
        if ($result['status']){
            return $this->successResponse(__('web.shop_status_change'), []);
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    public function nearbyShops(Request $request)
    {
        $shops = (new ShopDeliveryRepository())->findNearbyShops($request->clientLocation, $request->shopLocation ?? null);
        return $this->successResponse(__('web.list_of_shops'), ShopResource::collection($shops));
    }

    public function fileExport()
    {
        $time = Str::slug(Carbon::now()->format('Y-m-d h:i:s'));
        $fileName = 'export/' . $time . '-shops.xlsx';
        $file = Excel::store(new ShopExport(), $fileName, 'public');
        if ($file) {
            return $this->successResponse('Successfully exported', [
                'path' => 'public/export',
                'file_name' => $fileName
            ]);
        }
        return $this->errorResponse('Error during export');
    }
}
