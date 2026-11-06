<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * 各ユーザーに 3〜5 冊のお気に入りを設定する。
     * ユーザーと書籍は、登録した順の番号（1 から）で指す。
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get()->values();
        $bookIds = Book::orderBy('id')->pluck('id')->values();

        collect([
            1 => [3, 7, 10],
            2 => [1, 2, 6, 9],
            3 => [2, 4, 8, 10, 11],
            4 => [5, 6, 9],
            5 => [3, 7, 8, 11],
        ])->each(fn (array $bookNumbers, int $userNumber) => $users[$userNumber - 1]
            ->favoriteBooks()
            ->syncWithoutDetaching(collect($bookNumbers)->map(fn (int $number) => $bookIds[$number - 1])->all())
        );
    }
}
