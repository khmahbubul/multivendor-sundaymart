<?php

namespace App\Http\Controllers\API\v1\Auth;

use App\Helpers\ResponseError;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthService\AuthByEmail;
use App\Services\AuthService\AuthByMobilePhone;
use App\Services\SMSGatewayService\SMSBaseService;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        if (isset($request->phone)){
            return (new AuthByMobilePhone())->authentication($request->all());
        } elseif (isset($request->email)) {
            return (new AuthByEmail())->authentication($request->all());
        }
        return $this->errorResponse(ResponseError::ERROR_400, 'errors.'.ResponseError::ERROR_400, Response::HTTP_BAD_REQUEST);
    }
}
