<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 書籍登録・書籍編集の入力チェックの共通部分（F-03・F-04）。
 *
 * ISBN の一意性だけは、登録と編集で違う（編集は自分自身を除く）ため、サブクラスで足す。
 */
abstract class BookRequest extends FormRequest
{
    /**
     * 認可は、コントローラーの `$this->authorize()` で行う（Q-52）。ここでは常に通す。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ISBN 以外の入力チェックのルール（`docs/validation-rules.md`）。
     *
     * @return array<string, array<int, string>>
     */
    protected function commonRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'published_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:16000'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    /**
     * エラーメッセージ（`docs/screen-input-checks.md`）。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者は必須です。',
            'author.string' => '著者は文字列で入力してください。',
            'author.max' => '著者は255文字以内で入力してください。',
            'isbn.required' => 'ISBNは必須です。',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'このISBNはすでに登録されています。',
            'published_date.required' => '出版日は必須です。',
            'published_date.date_format' => '出版日は正しい日付で入力してください。',
            'description.string' => '説明は文字列で入力してください。',
            'description.max' => '説明は16000文字以内で入力してください。',
            'image_url.url' => '画像URLはURLの形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',
            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.array' => 'ジャンルの選択内容が正しくありません。',
            'genres.*.integer' => '選択したジャンルが正しくありません。',
            'genres.*.exists' => '選択したジャンルは存在しません。',
        ];
    }
}
