<?php

namespace App\Services\AuthService;

use App\Helpers\ResponseError;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository\UserRepository;
use App\Services\CoreService;
use Illuminate\Auth\Events\Registered;
use Symfony\Component\HttpFoundation\Response;

class AuthByEmail extends CoreService
{

    /**
     * @return mixed
     */
    protected function getModelClass()
    {
        return User::class;
    }

    public function authentication(array $array)
    {
        $user = $this->model()->firstWhere('email', $array['email']);

        if ($user) {
            return $this->errorResponse(ResponseError::ERROR_106, trans('errors.'. ResponseError::ERROR_106, [], request()->lang ?? 'en'), Response::HTTP_BAD_REQUEST);
        } else {
            $user = $this->model()->create([
                'firstname' => $array['firstname'],
                'email' => $array['email'],
                'phone' => '',
                'password' => bcrypt($array['password']),
                'ip_address' => request()->ip()
            ]);

            event(new Registered($user));

            return $this->errorResponse(ResponseError::ERROR_103, trans('errors.'. ResponseError::ERROR_103, [], request()->lang ?? 'en'), Response::HTTP_UNAUTHORIZED);
        }


    }

    public function confirmOPTCode()
    {

    }
}
