<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * レビュー投稿・レビュー編集の入力チェック（F-11・F-12）。
 *
 * 投稿と編集でルールとメッセージが同じなので、1 つの FormRequest で使い回す。
 * 違いが出たら、そのときに分ける。
 */
class ReviewRequest extends FormRequest
{
    /**
     * 認可は、コントローラーの `$this->authorize()` で行う（Q-52）。ここでは常に通す。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力チェックのルール（`docs/validation-rules.md`）。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
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
            'rating.required' => '評価を選択してください。',
            'rating.integer' => '評価は1〜5の中から選択してください。',
            'rating.between' => '評価は1〜5の中から選択してください。',
            'comment.required' => 'コメントを入力してください。',
            'comment.string' => 'コメントは文字列で入力してください。',
            'comment.max' => 'コメントは1000文字以内で入力してください。',
        ];
    }
}
