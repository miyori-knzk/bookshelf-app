<?php

namespace App\Http\Controllers;

/**
 * 読書計画の一覧・作成・編集・削除・読了（F-28〜F-32）。本体は段階 12 で作る。
 *
 * それまでは、どのメソッドも 501 を返す。引数と認可（所有者のみ）は、本体を作るときに足す。
 */
class ReadingPlanController extends Controller
{
    /**
     * 読書計画の一覧を表示する（F-28）。
     */
    public function index(): never
    {
        abort(501);
    }

    /**
     * 読書計画の作成画面を表示する（F-29）。
     */
    public function create(): never
    {
        abort(501);
    }

    /**
     * 読書計画を登録する（F-29）。
     */
    public function store(): never
    {
        abort(501);
    }

    /**
     * 読書計画の編集画面を表示する（F-30）。
     */
    public function edit(): never
    {
        abort(501);
    }

    /**
     * 読書計画を更新する（F-30）。
     */
    public function update(): never
    {
        abort(501);
    }

    /**
     * 読書計画を削除する（F-31）。
     */
    public function destroy(): never
    {
        abort(501);
    }

    /**
     * 読書計画を読了にする（F-32）。
     */
    public function complete(): never
    {
        abort(501);
    }
}
