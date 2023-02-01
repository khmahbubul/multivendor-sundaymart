<?php

namespace App\Http\Controllers\API\v1\Dashboard\Admin;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAllRequest;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Repositories\BlogRepository\BlogRepository;
use App\Services\BlogService\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends AdminBaseController
{

    private BlogRepository $blogRepository;
    private BlogService $blogService;

    /**
     * @param BlogRepository $blogRepository
     * @param BlogService $blogService
     */
    public function __construct(BlogRepository $blogRepository, BlogService $blogService)
    {
        parent::__construct();
        $this->blogRepository = $blogRepository;
        $this->blogService = $blogService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function paginate(Request $request): AnonymousResourceCollection
    {
        $blogs = $this->blogRepository->blogsPaginate($request->perPage ?? 15, null, $request->all());
        return BlogResource::collection($blogs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $result = $this->blogService->create($request);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), BlogResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $blog = $this->blogRepository->blogByUUID($uuid);
        if ($blog){
            return $this->successResponse(__('web.brand_found'), BlogResource::make($blog->load('translations')));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param string $uuid
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $uuid, Request $request): JsonResponse
    {
        $result = $this->blogService->update($uuid, $request);
        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_updated'), BlogResource::make($result['data']));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DeleteAllRequest $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function destroy(DeleteAllRequest $request)
    {
        $collection = $request->validated();

        $result = $this->blogService->delete($collection['ids']);

        if ($result['status']) {
            return $this->successResponse(__('web.record_has_been_successfully_delete'));
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function setActiveStatus(string $uuid): JsonResponse
    {
        $blog = Blog::firstWhere('uuid', $uuid);
        if ($blog) {
            $blog->update(['active' => !$blog->active]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), BlogResource::make($blog));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Change Active Status of Model.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function blogPublish(string $uuid)
    {
        $blog = Blog::firstWhere('uuid', $uuid);
        if ($blog) {
            if (!isset($blog->published_at)){
                $blog->update(['published_at' => today()]);
            }
            return $this->successResponse(__('web.record_has_been_successfully_updated'), BlogResource::make($blog));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }
}
