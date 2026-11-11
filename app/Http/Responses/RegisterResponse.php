<?php

namespace App\Http\Responses;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * 会員登録の後の応答を作る。
     *
     * Fortify の標準は、開こうとした画面へ戻る（`redirect()->intended()`）。
     * 設計書では会員登録の後は書籍一覧なので、保存された移動先は使わず、常に書籍一覧へ移動する（Q-61）。
     *
     * @param  Request  $request
     */
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect(RouteServiceProvider::HOME);
    }
}
