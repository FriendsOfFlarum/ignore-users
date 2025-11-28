<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class ByobuPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param User $user
     *
     * @return string|bool|null
     */
    public function cannotBeDirectMessaged(User $actor, User $user): string|bool|null
    {
        /** @phpstan-ignore-next-line */
        if (in_array($actor->id, $user->ignoredUsers()->pluck('id')->all())) {
            return $this->deny();
        }

        return null;
    }
}
