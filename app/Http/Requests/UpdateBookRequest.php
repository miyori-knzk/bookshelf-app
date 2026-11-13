<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Validation\Rule;

/**
 * 書籍編集の入力チェック（F-04）。
 */
class UpdateBookRequest extends BookRequest
{
    /**
     * 入力チェックのルール。ISBN の一意性のチェックからは、編集中の書籍自身を除く。
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Book $book */
        $book = $this->route('book');

        return [
            ...$this->commonRules(),
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($book)],
        ];
    }
}
