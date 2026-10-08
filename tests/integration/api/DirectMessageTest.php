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

use Flarum\Messages\Dialog;
use FoF\IgnoreUsers\Tests\integration\IgnoreTestCase;
use PHPUnit\Framework\Attributes\Test;

class DirectMessageTest extends IgnoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages', 'fof-ignore-users');

        $this->seedUsers();
    }

    #[Test]
    public function ignored_user_cannot_start_a_dialog_with_the_user_ignoring_them(): void
    {
        $response = $this->sendNewMessage(2, 3);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(0, Dialog::query()->count());
    }

    #[Test]
    public function user_can_message_someone_who_does_not_ignore_them(): void
    {
        $response = $this->sendNewMessage(2, 4);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function ignoring_is_one_directional(): void
    {
        $response = $this->sendNewMessage(3, 2);

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function ignored_user_cannot_continue_an_existing_dialog(): void
    {
        $this->prepareDatabase([
            Dialog::class => [['id' => 1, 'type' => 'direct']],
            'dialog_user' => [
                ['dialog_id' => 1, 'user_id' => 2, 'joined_at' => '2026-01-01 00:00:00'],
                ['dialog_id' => 1, 'user_id' => 3, 'joined_at' => '2026-01-01 00:00:00'],
            ],
        ]);

        $response = $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => 2,
            'json' => ['data' => [
                'type' => 'dialog-messages',
                'attributes' => ['content' => 'Still there?'],
                'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '1']]],
            ]],
        ]));

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function ignoring_a_user_through_the_api_blocks_their_messages(): void
    {
        $before = $this->sendNewMessage(2, 4);
        $this->assertEquals(201, $before->getStatusCode(), (string) $before->getBody());

        $ignore = $this->setIgnored(4, 2, true);
        $this->assertEquals(200, $ignore->getStatusCode(), (string) $ignore->getBody());

        $after = $this->sendNewMessage(2, 4);
        $this->assertEquals(403, $after->getStatusCode(), (string) $after->getBody());
    }
}
