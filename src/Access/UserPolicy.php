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

class UserPolicy extends AbstractPolicy
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
    public function ignore(User $actor, User $user)
    {
        if ($user->can('notBeIgnored') || $user->id === $actor->id) {
            return false;
        }
        return true;
    }
}
