<?php

namespace App\Http\Controllers\Api;

use App\Services\AccountingService;
use Auth;
use App\User;
use App\Http\Requests\Api\LoginUser;
use App\Http\Requests\Api\RegisterUser;
use App\RealWorld\Transformers\UserTransformer;
use Illuminate\Support\Facades\DB;

class AuthController extends ApiController
{
    /**
     * AuthController constructor.
     *
     * @param UserTransformer $transformer
     */
    public function __construct(UserTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Login user and return the user if successful.
     *
     * @param LoginUser $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginUser $request)
    {
        $credentials = $request->only('user.email', 'user.password');
        $credentials = $credentials['user'];

        if (! Auth::once($credentials)) {
            return $this->respondFailedLogin();
        }

        return $this->respondWithTransformer(auth()->user());
    }

    /**
     * Register a new user and return the user if successful.
     *
     * @param RegisterUser $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterUser $request, AccountingService $accountingService)
    {
        try {
            DB::beginTransaction();
            $user = User::create([
                'username' => $request->input('user.username'),
                'email'    => $request->input('user.email'),
                'password' => bcrypt($request->input('user.password')),
            ]);

            $accountingService->chargeWallet($user->id,100000);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollback();
            throw $exception;
        }
        return $this->respondWithTransformer($user);
    }
}
