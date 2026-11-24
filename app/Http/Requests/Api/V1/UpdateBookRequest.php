<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use Illuminate\Validation\Rule;

/**
 * 公開API の書籍更新の入力チェック（F-24）。
 *
 * 書籍の項目は Web の書籍編集と同じ（Q-28）。`user_id` は任意で、送られたときだけ検証して、登録者を変える（Q-41）。
 */
class UpdateBookRequest extends BookRequest
{
    /**
     * 入力チェックのルール。ISBN の一意性のチェックからは、更新する書籍自身を除く。
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Book $book */
        $book = $this->route('book');

        return [
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            ...$this->commonRules(),
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($book)],
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
            'user_id.integer' => '登録者IDは整数で指定してください。',
            'user_id.exists' => '登録者が存在しません。',
        ];
    }
}
