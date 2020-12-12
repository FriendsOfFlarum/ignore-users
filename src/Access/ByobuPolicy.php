<?php

/**
 *
 *  This file is part of fof/ignore-users.
 *
 *  Copyright (c) 2019 FriendsOfFlarum..
 *
 *  For the full copyright and license information, please view the license.md
 *  file that was distributed with this source code.
 *
 */

namespace FoF\IgnoreUsers\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class ByobuPolicy extends AbstractPolicy
{
    /**
     * {@inheritdoc}
     */
    protected $model = User::class;

    /**
     * @param  User $actor
     * @param  User $user
     * @return bool|null
     */
    public function cannotBeDirectMessaged(User $actor, User $user)
    {
        if (in_array($actor->id, $user->ignoredUsers()->pluck('id')->all())) {
            return true;
        }
    }
}
