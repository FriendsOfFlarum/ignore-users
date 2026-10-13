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
use FoF\IgnoreUsers\User\IgnoredUserIds;

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
        // Checked first, so that a guest's check doesn't load the user's permissions.
        if ($actor->isGuest()) {
            return $this->deny();
        }

        if ($user->hasPermission('notBeIgnored') || $user->id === $actor->id) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * Whether $actor may start or continue a direct message with $recipient.
     */
    public function sendDirectMessage(User $actor, User $recipient): string|bool|null
    {
        // Explicit allow: with no policy result Gate would fall back to a group permission named after the ability.
        return $this->isIgnoredBy($actor, $recipient) ? $this->deny() : $this->allow();
    }

    /**
     * Asked by flarum/messages before anything reaches $user. Only ever denies,
     * so messages' own rules decide everything else.
     */
    public function message(User $actor, User $user): ?string
    {
        return $this->isIgnoredBy($actor, $user) ? $this->deny() : null;
    }

    /**
     * Whether $user ignores $actor. Users holding `notBeIgnored` (e.g. staff)
     * are exempt, mirroring `ignore()`.
     */
    protected function isIgnoredBy(User $actor, User $user): bool
    {
        return $actor->id !== $user->id
            && !$actor->hasPermission('notBeIgnored')
            && IgnoredUserIds::isIgnoredBy($actor, $user);
    }
}
