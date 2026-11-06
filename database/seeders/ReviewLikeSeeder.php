<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * 各レビューに 0〜3 人のユーザーがいいねする（自分のレビューを除く）。
     * レビューとユーザーは、登録した順の番号（1 から）で指す。
     */
    public function run(): void
    {
        $userIds = User::orderBy('id')->pluck('id')->values();
        $reviews = Review::orderBy('id')->get()->values();

        collect([
            1 => [3, 4],
            2 => [1],
            3 => [],
            4 => [1, 2, 3],
            5 => [2],
            6 => [1, 5],
            7 => [2, 3, 5],
            8 => [3],
            9 => [1, 4],
            10 => [],
            11 => [1, 3, 4],
            12 => [5],
            13 => [2, 4],
            14 => [],
            15 => [2],
            16 => [1, 5],
            17 => [3, 4, 5],
            18 => [1],
            19 => [],
            20 => [2, 5],
            21 => [3],
            22 => [1, 2, 5],
            23 => [4, 5],
            24 => [2],
            25 => [],
            26 => [3, 4],
            27 => [1],
            28 => [2, 3, 4],
            29 => [2, 3],
            30 => [1],
            31 => [4],
            32 => [1, 2, 5],
        ])->each(fn (array $userNumbers, int $reviewNumber) => $reviews[$reviewNumber - 1]
            ->likedByUsers()
            ->syncWithoutDetaching(collect($userNumbers)->map(fn (int $number) => $userIds[$number - 1])->all())
        );
    }
}
