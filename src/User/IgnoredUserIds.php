<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\User;

use Flarum\User\User;
use WeakMap;

/**
 * Whom an actor ignores, read as ids from the ignore table once per actor
 * instance (in effect, once per request) rather than as whole users.
 */
class IgnoredUserIds
{
    /**
     * Weak, so that an actor's ids go when the actor does.
     *
     * @var WeakMap<User, array<int, true>>|null
     */
    protected static ?WeakMap $ids = null;

    public static function contains(User $actor, User $user): bool
    {
        return isset(self::of($actor)[$user->id]);
    }

    /**
     * Drop what's held for the actor, after their ignore list has changed.
     */
    public static function forget(User $actor): void
    {
        if (self::$ids !== null) {
            unset(self::$ids[$actor]);
        }
    }

    /**
     * @return array<int, true>
     */
    protected static function of(User $actor): array
    {
        self::$ids ??= new WeakMap();

        /** @phpstan-ignore-next-line */
        return self::$ids[$actor] ??= array_fill_keys($actor->ignoredUsers()->allRelatedIds()->all(), true);
    }
}
