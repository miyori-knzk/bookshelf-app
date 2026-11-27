<?php

namespace App\Http\Controllers;

/**
 * 通知の一覧と既読化（F-34・F-35）。本体は段階 13 で作る。
 *
 * それまでは、どのメソッドも 501 を返す。引数と認可（本人のみ）は、本体を作るときに足す。
 */
class NotificationController extends Controller
{
    /**
     * 通知の一覧を表示する（F-34）。
     */
    public function index(): never
    {
        abort(501);
    }

    /**
     * 通知を既読にする（F-35）。
     */
    public function read(): never
    {
        abort(501);
    }
}
