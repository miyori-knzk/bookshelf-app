<?php

namespace App\Http\Requests;

/**
 * 書籍登録の入力チェック（F-03）。
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
            ...$this->commonRules(),
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
        ];
    }
}
