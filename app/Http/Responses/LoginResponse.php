<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('app.login_success')]);

        return redirect()->intended(
            $request->user()?->hasVerifiedEmail()
                ? Fortify::redirects('login', config('fortify.home'))
                : route('verification.notice')
        );
    }
}
