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

class UserPolicy extends AbstractPolicy
{
    /**
     * @param User $actor
     * @param User $user
     *
     * @return string|bool|null
     */
    public function ignore(User $actor, User $user): string|bool|null
    {
        if ($user->hasPermission('notBeIgnored') || $user->id === $actor->id) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * Whether $actor may start or continue a direct message with $recipient.
     * Users holding `notBeIgnored` (e.g. staff) are exempt, mirroring `ignore()`.
     */
    public function sendDirectMessage(User $actor, User $recipient): string|bool|null
    {
        if ($actor->id === $recipient->id || $actor->hasPermission('notBeIgnored')) {
            return $this->allow();
        }

        /** @phpstan-ignore-next-line */
        if ($recipient->ignoredUsers()->where('ignored_user_id', $actor->id)->exists()) {
            return $this->deny();
        }

        // Explicit allow: with no policy result Gate would fall back to a group permission named after the ability.
        return $this->allow();
    }
}
