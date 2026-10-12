<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Tests\integration\user;

use Flarum\User\User;
use FoF\IgnoreUsers\Tests\integration\IgnoreTestCase;
use FoF\IgnoreUsers\User\IgnoredUserIds;
use PHPUnit\Framework\Attributes\Test;

class IgnoredUserIdsTest extends IgnoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-ignore-users');

        $this->seedUsers();
    }

    #[Test]
    public function it_reads_whom_the_actor_ignores_once_until_told_to_forget(): void
    {
        $this->app();

        $actor = User::findOrFail(4);
        $other = User::findOrFail(2);

        $this->assertFalse(IgnoredUserIds::contains($actor, $other));

        /** @phpstan-ignore-next-line */
        $actor->ignoredUsers()->attach($other, ['ignored_at' => '2026-01-01 00:00:00']);

        $this->assertFalse(IgnoredUserIds::contains($actor, $other), 'Read again rather than held for the request.');

        IgnoredUserIds::forget($actor);

        $this->assertTrue(IgnoredUserIds::contains($actor, $other));
    }
}
