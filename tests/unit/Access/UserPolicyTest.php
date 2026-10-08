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

    private function recipientIgnoring(bool $ignores): User
    {
        $recipient = $this->user(3);

        $relation = m::mock(BelongsToMany::class);
        $relation->shouldReceive('where')->with('ignored_user_id', 2)->andReturnSelf();
        $relation->shouldReceive('exists')->andReturn($ignores);
        $recipient->shouldReceive('ignoredUsers')->andReturn($relation);

        return $recipient;
    }

    #[Test]
    public function denies_when_the_recipient_ignores_the_actor(): void
    {
        $policy = new UserPolicy();

        $this->assertSame(
            UserPolicy::DENY,
            $policy->sendDirectMessage($this->user(2), $this->recipientIgnoring(true))
        );
    }

    #[Test]
    public function allows_when_the_recipient_does_not_ignore_the_actor(): void
    {
        $policy = new UserPolicy();

        $this->assertSame(
            UserPolicy::ALLOW,
            $policy->sendDirectMessage($this->user(2), $this->recipientIgnoring(false))
        );
    }

    #[Test]
    public function allows_an_actor_who_cannot_be_ignored_without_querying(): void
    {
        $recipient = $this->user(3);
        $recipient->shouldNotReceive('ignoredUsers');

        $this->assertSame(UserPolicy::ALLOW, (new UserPolicy())->sendDirectMessage($this->user(2, true), $recipient));
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }
}
