<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/**
 * レビューの認可。編集・削除は、レビューを投稿した本人だけに許す（Q-47）。
 */
class ReviewPolicy
{
    /**
     * レビューを編集・更新できるか（F-12）。
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * レビューを削除できるか（F-13）。
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
