<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * 初期ユーザー 5 件を登録する。email が同じユーザーは作り直さない。
     */
    public function run(): void
    {
        collect([
            ['name' => '山田太郎', 'email' => 'yamada@example.com'],
            ['name' => '鈴木花子', 'email' => 'suzuki@example.com'],
            ['name' => '田中一郎', 'email' => 'tanaka@example.com'],
            ['name' => '佐藤美咲', 'email' => 'sato@example.com'],
            ['name' => '高橋健太', 'email' => 'takahashi@example.com'],
        ])->each(fn (array $user) => User::firstOrCreate(
            ['email' => $user['email']],
            ['name' => $user['name'], 'password' => Hash::make('password')],
        ));
    }
}
