<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Tests\integration\api;

use Flarum\Group\Group;
use FoF\IgnoreUsers\Tests\integration\IgnoreTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Users holding `notBeIgnored` (seeded: user 5 via group 10) and administrators (user 1) are exempt from being ignored.
 * User 3 ignores users 2 and 5, the latter being data left over from before 5 was given the permission.
 */
class CannotBeIgnoredTest extends IgnoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages', 'flarum-tags', 'fof-byobu', 'fof-ignore-users');

        $this->seedUsers();

        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.startPrivateDiscussionWithUsers'],
                ['group_id' => 10, 'permission' => 'discussion.startPrivateDiscussionWithUsers'],
            ],
            'ignored_user' => [
                ['user_id' => 3, 'ignored_user_id' => 1, 'ignored_at' => '2026-01-01 00:00:00'],
            ],
        ]);
    }

    private function ignoredRows(int $user, int $ignored): int
    {
        return $this->database()->table('ignored_user')->where(['user_id' => $user, 'ignored_user_id' => $ignored])->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(ResponseInterface $response): array
    {
        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    #[Test]
    public function user_with_the_permission_cannot_be_ignored(): void
    {
        $response = $this->setIgnored(2, 5, true);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, $this->ignoredRows(2, 5));
    }

    #[Test]
    public function administrator_cannot_be_ignored(): void
    {
        $response = $this->setIgnored(2, 1, true);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, $this->ignoredRows(2, 1));
    }

    #[Test]
    public function api_reports_whether_a_user_can_be_ignored(): void
    {
        $this->assertTrue($this->attributes($this->send($this->request('GET', '/api/users/4', ['authenticatedAs' => 2])))['canBeIgnored']);
        $this->assertFalse($this->attributes($this->send($this->request('GET', '/api/users/5', ['authenticatedAs' => 2])))['canBeIgnored']);
        $this->assertFalse($this->attributes($this->send($this->request('GET', '/api/users/1', ['authenticatedAs' => 2])))['canBeIgnored']);
    }

    #[Test]
    public function exempt_user_is_not_reported_as_ignored_even_with_a_stale_ignore(): void
    {
        $ignoring = fn (int $target) => $this->attributes($this->send($this->request('GET', "/api/users/$target", ['authenticatedAs' => 3])))['ignored'];

        $this->assertTrue($ignoring(2), 'control: a normal ignored user is reported as ignored');
        $this->assertFalse($ignoring(5), 'user 5 gained the permission after being ignored');
        $this->assertFalse($ignoring(1), 'administrators are never reported as ignored');
    }

    #[Test]
    public function exempt_user_can_start_a_dialog_with_someone_who_ignores_them(): void
    {
        $this->assertEquals(201, ($response = $this->sendNewMessage(5, 3))->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function administrator_can_start_a_dialog_with_someone_who_ignores_them(): void
    {
        $this->assertEquals(201, ($response = $this->sendNewMessage(1, 3))->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function exempt_user_can_start_a_private_discussion_with_someone_who_ignores_them(): void
    {
        $this->assertEquals(201, ($response = $this->startPrivateDiscussion(5, 3))->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function administrator_can_start_a_private_discussion_with_someone_who_ignores_them(): void
    {
        $this->assertEquals(201, ($response = $this->startPrivateDiscussion(1, 3))->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function exempt_user_can_be_messaged_by_anyone(): void
    {
        $failedAttempt = $this->setIgnored(2, 5, true);
        $this->assertEquals(403, $failedAttempt->getStatusCode(), (string) $failedAttempt->getBody());

        $this->assertEquals(201, ($response = $this->sendNewMessage(2, 5))->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function exempt_user_can_be_invited_to_a_private_discussion_by_anyone(): void
    {
        $failedAttempt = $this->setIgnored(2, 5, true);
        $this->assertEquals(403, $failedAttempt->getStatusCode(), (string) $failedAttempt->getBody());

        $this->assertEquals(201, ($response = $this->startPrivateDiscussion(2, 5))->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function exemption_protects_a_user_from_being_ignored_but_not_from_ignoring_others(): void
    {
        $ignore = $this->setIgnored(5, 2, true);
        $this->assertEquals(200, $ignore->getStatusCode(), (string) $ignore->getBody());

        $response = $this->sendNewMessage(2, 5);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }
}
