<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    /**
     * ジャンル 10 件を登録する。name が同じジャンルは作り直さない。
     */
    public function run(): void
    {
        collect([
            '小説',
            'ビジネス',
            '技術書',
            '自己啓発',
            'エッセイ',
            '歴史',
            '科学',
            '芸術',
            '料理',
            '旅行',
        ])->each(fn (string $name) => Genre::firstOrCreate(['name' => $name]));
    }
}
