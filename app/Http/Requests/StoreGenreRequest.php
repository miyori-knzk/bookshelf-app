<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ジャンル登録の入力チェック（F-08）。
 */
class StoreGenreRequest extends FormRequest
{
    /**
     * ログインの確認は `auth` ミドルウェアが行う。ジャンルに認可の判定は無い（Q-16）。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力チェックのルール。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:genres,name'],
        ];
    }

    /**
     * 項目名。会員登録の `name`（お名前）と区別する。
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'ジャンル名',
        ];
    }

    /**
     * エラーメッセージ。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'このジャンル名はすでに登録されています。',
        ];
    }
}
