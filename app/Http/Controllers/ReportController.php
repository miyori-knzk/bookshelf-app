<?php

namespace App\Http\Controllers;

/**
 * マイ読書レポート（F-36）。本体は段階 14 で作る。
 */
class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する。段階 14 までは 501 を返す。
     */
    public function index(): never
    {
        abort(501);
    }
}
