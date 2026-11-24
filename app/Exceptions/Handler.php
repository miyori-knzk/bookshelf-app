<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * 公開API（`/api/` の URL）のエラーは、`message` を日本語にして JSON で返す（Q-53。`docs/error-handling.md`）。
     * Web のエラー画面は変えない。
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // ModelNotFoundException は、ここに届く前に NotFoundHttpException に変わっている（前の例外として残る）
        $this->renderable(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            if (! $this->isApiRequest($request)) {
                return null;
            }

            $message = $e->getPrevious() instanceof ModelNotFoundException
                ? '書籍が見つかりません。'
                : '指定されたURLは存在しません。';

            return response()->json(['message' => $message], 404);
        });

        $this->renderable(fn (MethodNotAllowedHttpException $e, Request $request): ?JsonResponse => $this->apiError(
            $request, 'このメソッドには対応していません。', $e
        ));

        $this->renderable(fn (TooManyRequestsHttpException $e, Request $request): ?JsonResponse => $this->apiError(
            $request, 'リクエストが多すぎます。しばらくしてからもう一度お試しください。', $e
        ));
    }

    /**
     * 公開API は、`Accept` ヘッダーが無くても、エラーを必ず JSON で返す（入力エラーのリダイレクトを含む）。
     *
     * @param  Request  $request
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $this->isApiRequest($request) || parent::shouldReturnJson($request, $e);
    }

    /**
     * 公開API の予期しないエラー（500）は、詳細を返さず、日本語の `message` だけを返す。
     * 開発中（`APP_DEBUG=true`）は、Laravel 標準のまま詳細を返す。
     *
     * @param  Request  $request
     */
    protected function prepareJsonResponse($request, Throwable $e): JsonResponse
    {
        if ($this->isApiRequest($request) && ! config('app.debug') && ! $e instanceof HttpExceptionInterface) {
            return response()->json(['message' => 'サーバーでエラーが発生しました。'], 500);
        }

        return parent::prepareJsonResponse($request, $e);
    }

    /**
     * 公開API の URL（`/api/` から始まる）へのリクエストか。
     */
    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*');
    }

    /**
     * 公開API のとき、指定した `message` の JSON を、例外のステータス・ヘッダーで返す。Web のときは `null`。
     */
    private function apiError(Request $request, string $message, HttpExceptionInterface $e): ?JsonResponse
    {
        if (! $this->isApiRequest($request)) {
            return null;
        }

        return response()->json(['message' => $message], $e->getStatusCode(), $e->getHeaders());
    }
}
