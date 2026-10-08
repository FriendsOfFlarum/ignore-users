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

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use FoF\IgnoreUsers\Tests\integration\IgnoreTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class ByobuPrivateDiscussionTest extends IgnoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'fof-byobu', 'fof-ignore-users');

        $this->seedUsers();

        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.startPrivateDiscussionWithUsers'],
            ],
        ]);
    }

    /**
     * Byobu/core resolve each submitted recipient with a per-id `users` lookup when writing the relationship
     * (present with this extension's listener disabled); our own check is a single batched query.
     */
    protected function allowedRepeatedQueries(): array
    {
        return ['from `users` where `users`.`id` ='];
    }

    #[Test]
    public function ignored_user_cannot_start_a_private_discussion_with_the_user_ignoring_them(): void
    {
        $response = $this->startPrivateDiscussion(2, 3);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, Discussion::query()->count());
    }

    #[Test]
    public function user_can_start_a_private_discussion_with_someone_who_does_not_ignore_them(): void
    {
        $response = $this->startPrivateDiscussion(2, 4);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function ignoring_is_one_directional(): void
    {
        $response = $this->startPrivateDiscussion(3, 2);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    private function seedExistingPrivateDiscussion(int $otherRecipient = 4): void
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.editUserRecipients'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.addMoreThanTwoUserRecipients'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Existing', 'created_at' => '2026-01-01 00:00:00', 'user_id' => 2, 'comment_count' => 0, 'is_private' => 1],
            ],
            'recipients' => [
                ['discussion_id' => 1, 'user_id' => 2, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
                ['discussion_id' => 1, 'user_id' => $otherRecipient, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'],
            ],
        ]);
    }

    private function setRecipients(int $actor, int ...$recipients): ResponseInterface
    {
        return $this->send($this->request('PATCH', '/api/discussions/1', [
            'authenticatedAs' => $actor,
            'json'            => ['data' => [
                'type'          => 'discussions',
                'id'            => '1',
                'relationships' => ['recipientUsers' => ['data' => array_map(
                    fn (int $id) => ['type' => 'users', 'id' => (string) $id],
                    $recipients
                )]],
            ]],
        ]));
    }

    #[Test]
    public function user_who_may_edit_recipients_can_add_someone_who_does_not_ignore_them(): void
    {
        // Control for the next test: proves the PATCH is otherwise accepted, so a 403 there comes from this extension.
        $this->seedExistingPrivateDiscussion();

        $response = $this->setRecipients(2, 2, 4, 6);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function ignored_user_cannot_be_added_to_an_existing_private_discussion_by_someone_the_recipient_ignores(): void
    {
        $this->seedExistingPrivateDiscussion();

        $response = $this->setRecipients(2, 2, 4, 3);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function existing_recipients_who_ignore_the_actor_are_not_rechecked(): void
    {
        $this->seedExistingPrivateDiscussion(3);

        $response = $this->setRecipients(2, 2, 3, 6);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }
}
