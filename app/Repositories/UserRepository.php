<?php

namespace App\Repository;

use App\User;
use Exception;

class UserRepository
{
    /**
     * Get user
     * 
     * @param integer $userId User id
     * @return User User instance
     */
    public function get ($userId)
    {
        $user = User::find($userId);
        if (!$user) throw new Exception('User not found');
        return $user;
    }

    /**
     * Reset avatar and avatar_original to default image
     * 
     * @param User $user User instance
     * @return void
     */
    public function resetAvatar (User $user)
    {
        $defaultAvatar = 'https://winoutt-prod.s3.us-east-2.amazonaws.com/assets/default-avatar.png';
        $user->avatar = $defaultAvatar;
        $user->avatar_original = $defaultAvatar;
        $user->save();
    }
}