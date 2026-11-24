<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 公開API の書籍一覧の入力チェック（F-21）。クエリ文字列は、すべて任意（Q-43）。
 */
class IndexBookRequest extends FormRequest
{
    /**
     * 基本機能の公開API は認証しない。ここでは常に通す。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力チェックのルール（`docs/validation-rules.md`「公開API 書籍一覧」）。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    /**
     * エラーメッセージ（`docs/api-spec.md`「入力エラーのメッセージ」）。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で指定してください。',
            'keyword.max' => 'キーワードは255文字以内で指定してください。',
            'genre_id.integer' => 'ジャンルIDは整数で指定してください。',
            'genre_id.exists' => '指定したジャンルは存在しません。',
            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',
            'per_page.integer' => 'ページあたり件数は整数で指定してください。',
            'per_page.between' => 'ページあたり件数は1〜100で指定してください。',
        ];
    }
}
