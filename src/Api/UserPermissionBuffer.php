<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Api;

use Closure;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use WeakMap;

/**
 * Defers checks of serialized users' permissions until the API document has
 * been built, so that the groups their permissions come from are loaded with
 * one query for every user, rather than one query per user.
 */
class UserPermissionBuffer
{
    /**
     * Weak, so that a user whose check never resolves isn't kept in memory.
     *
     * @var WeakMap<User, true>|null
     */
    protected static ?WeakMap $users = null;

    /**
     * @param callable(): bool $check needs the user's permissions
     *
     * @return Closure(): bool
     */
    public static function defer(User $user, callable $check): Closure
    {
        self::$users ??= new WeakMap();
        self::$users[$user] = true;

        return function () use ($check): bool {
            self::loadGroups();

            return $check();
        };
    }

    protected static function loadGroups(): void
    {
        $pending = [];

        foreach (self::$users ?? [] as $user => $_) {
            if (!$user->relationLoaded('groups')) {
                $pending[] = $user;
            }
        }

        self::$users = null;

        // Chunked to stay within the database's limit on bound parameters.
        foreach (array_chunk($pending, 1000) as $users) {
            (new Collection($users))->load('groups');
        }
    }
}
