<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\BookRequest;

/**
 * 公開API の書籍登録の入力チェック（F-23）。
 *
 * 書籍の項目は Web の書籍登録と同じ（Q-28）。登録者は `user_id` で受け取る（Q-26）。
 */
class StoreBookRequest extends BookRequest
{
    /**
     * 入力チェックのルール。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            ...$this->commonRules(),
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
        ];
    }

    /**
     * エラーメッセージ。書籍の項目は Web と同じで、`user_id` の分を足す（`docs/api-spec.md`）。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'user_id.required' => '登録者IDは必須です。',
            'user_id.integer' => '登録者IDは整数で指定してください。',
            'user_id.exists' => '登録者が存在しません。',
        ];
    }
}
