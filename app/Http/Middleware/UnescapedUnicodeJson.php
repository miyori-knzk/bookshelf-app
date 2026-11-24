<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 公開API の JSON で、日本語を `\u5c71`（「山」の文字コード）のようにエスケープせず、そのまま出す（Q-74）。
 *
 * 全体のミドルウェアにしているのは、ルートが無いときの 404 など、`api` グループを通らないエラーも対象にするため。
 * `/api/` から始まる URL の、JSON の応答だけを対象にする。Web の応答には触れない。
 */
class UnescapedUnicodeJson
{
    /**
     * `/api/` の JSON の応答に、`JSON_UNESCAPED_UNICODE` を付ける。
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('api/*') && $response instanceof JsonResponse) {
            $response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);
        }

        return $response;
    }
}
