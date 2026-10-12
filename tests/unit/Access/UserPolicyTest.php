<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Tests\unit\Access;

use Flarum\User\User;
use FoF\IgnoreUsers\Access\UserPolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    private function user(int $id, bool $exempt = false): User
    {
        $user = m::mock(User::class)->makePartial();
        $user->forceFill(['id' => $id]);
        $user->shouldReceive('hasPermission')->with('notBeIgnored')->andReturn($exempt);

        return $user;
    }

    /**
     * An actor (2) who is ignored by the given users; the ids stand in for the ignore table.
     *
     * @param int[] $ignoredBy
     */
    private function actorIgnoredBy(array $ignoredBy, bool $exempt = false): User
    {
        $actor = $this->user(2, $exempt);

        $relation = m::mock(BelongsToMany::class);
        $relation->shouldReceive('allRelatedIds')->andReturn(new Collection($ignoredBy));
        $actor->shouldReceive('ignoredBy')->andReturn($relation);

        return $actor;
    }

    #[Test]
    public function denies_direct_messages_to_a_user_who_ignores_the_actor(): void
    {
        $this->assertSame(UserPolicy::DENY, (new UserPolicy())->sendDirectMessage($this->actorIgnoredBy([3]), $this->user(3)));
    }

    #[Test]
    public function allows_direct_messages_to_a_user_who_does_not_ignore_the_actor(): void
    {
        $this->assertSame(UserPolicy::ALLOW, (new UserPolicy())->sendDirectMessage($this->actorIgnoredBy([4]), $this->user(3)));
    }

    #[Test]
    public function an_actor_who_cannot_be_ignored_is_allowed_without_reading_the_ignore_table(): void
    {
        $actor = $this->user(2, true);
        $actor->shouldNotReceive('ignoredBy');

        $this->assertSame(UserPolicy::ALLOW, (new UserPolicy())->sendDirectMessage($actor, $this->user(3)));
    }

    #[Test]
    public function tells_messages_the_actor_cannot_message_a_user_who_ignores_them(): void
    {
        $this->assertSame(UserPolicy::DENY, (new UserPolicy())->message($this->actorIgnoredBy([3]), $this->user(3)));
    }

    #[Test]
    public function leaves_messages_to_decide_otherwise(): void
    {
        $this->assertNull((new UserPolicy())->message($this->actorIgnoredBy([]), $this->user(3)));
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
