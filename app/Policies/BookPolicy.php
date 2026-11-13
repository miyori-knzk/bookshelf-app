<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

/**
 * 書籍の認可。編集・削除は、書籍を登録した本人だけに許す（Q-33・Q-47）。
 */
class BookPolicy
{
    /**
     * 書籍を編集・更新できるか（F-04）。
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * 書籍を削除できるか（F-05）。
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
