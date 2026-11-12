<?php

namespace App\Http\Requests;

use App\Models\Genre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ジャンル編集の入力チェック（F-09）。
 */
class UpdateGenreRequest extends FormRequest
{
    /**
     * ログインの確認は `auth` ミドルウェアが行う。ジャンルに認可の判定は無い（Q-16）。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力チェックのルール。一意性のチェックからは、編集中のジャンル自身を除く。
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Genre $genre */
        $genre = $this->route('genre');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('genres', 'name')->ignore($genre)],
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
