<?php

namespace App\Http\Controllers\API\v1\Dashboard\User;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\UserCreateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository\UserRepository;
use App\Services\UserServices\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends UserBaseController
{
    private  UserRepository $userRepository;
    private  UserService $userService;

    /**
     * @param UserRepository $userRepository
     * @param UserService $userService
     */
    public function __construct(UserRepository $userRepository, UserService $userService)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param UserCreateRequest $request
     * @return JsonResponse
     */
    public function store(UserCreateRequest $request)
    {
        $result = $this->userService->create($request);

        if ($result['status']) {
            return $this->successResponse(__('web.record_successfully_created'), $request['data']);
        }
        return $this->errorResponse(
            $result['code'], $result['message'] ?? trans('errors.' . $result['code'], [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     */
    public function show()
    {
        $user = $this->userRepository->userById(auth('sanctum')->id());
        if ($user) {
            return $this->successResponse(__('web.user_found'), UserResource::make($user));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404,  trans('errors.' . ResponseError::ERROR_404, [], request()->lang),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection|\Illuminate\Http\Response
     */
    public function update(UserCreateRequest $request)
    {
        $result = $this->userService->update(auth('sanctum')->user()->uuid, $request);

        if ($result['status']){
            return $this->successResponse(__('web.user_updated'), UserResource::make($result['data']));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, $result['message'] ?? trans('errors.' . ResponseError::ERROR_404, [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return JsonResponse
     */
    public function delete()
    {
        $user = $this->userRepository->userByUUID(auth('sanctum')->user()->uuid);
        if ($user) {
            $user->delete();
            return $this->successResponse(__('web.record_has_been_successfully_deleted'), []);
        } else {
            return $this->errorResponse(
                ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], \request()->lang ?? 'en'),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function fireBaseTokenUpdate(Request $request)
    {
        $user = User::firstWhere('uuid', auth('sanctum')->user()->uuid);
        if ($user) {
            $user->update(['firebase_token' => $request->firebase_token]);
            return $this->successResponse(__('web.record_has_been_successfully_updated'), []);
        } else {
            return $this->errorResponse(
                ResponseError::ERROR_404, trans('errors.' . ResponseError::ERROR_404, [], \request()->lang ?? 'en'),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function passwordUpdate(PasswordUpdateRequest $request)
    {
        $result = $this->userService->updatePassword(auth('sanctum')->user()->uuid, $request->password);
        if ($result['status']){
            return $this->successResponse(__('web.user_password_updated'), UserResource::make($result['data']));
        }
        return $this->errorResponse(
            ResponseError::ERROR_404, $result['message'] ?? trans('errors.' . ResponseError::ERROR_404, [], \request()->lang),
            Response::HTTP_BAD_REQUEST
        );
    }
}
