<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Seeds: 1 admin, 2 sender, 3 recipient who ignores 2, 4 recipient who does not,
 * 5 sender holding `notBeIgnored` via group 10, 6 bystander.
 */
abstract class IgnoreTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function seedUsers(): void
    {
        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                $this->user(3, 'ignorer'),
                $this->user(4, 'open'),
                $this->user(5, 'exempt'),
                $this->user(6, 'another'),
            ],
            'groups' => [
                ['id' => 10, 'name_singular' => 'Exempt', 'name_plural' => 'Exempt'],
            ],
            'group_user' => [
                ['user_id' => 5, 'group_id' => 10],
            ],
            'group_permission' => [
                ['group_id' => 10, 'permission' => 'notBeIgnored'],
            ],
            'ignored_user' => [
                ['user_id' => 3, 'ignored_user_id' => 2, 'ignored_at' => '2026-01-01 00:00:00'],
                ['user_id' => 3, 'ignored_user_id' => 5, 'ignored_at' => '2026-01-01 00:00:00'],
            ],
        ]);
    }

    protected function sendNewMessage(int $actor, int $recipient): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => $actor,
            'json'            => ['data' => ['type' => 'dialog-messages', 'attributes' => [
                'content' => 'Hello there',
                'users'   => [['id' => $recipient]],
            ]]],
        ]));
    }

    protected function startPrivateDiscussion(int $actor, int $recipient): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/discussions', [
            'authenticatedAs' => $actor,
            'json'            => ['data' => [
                'type'          => 'discussions',
                'attributes'    => ['title' => 'A private chat', 'content' => 'Hello in private'],
                'relationships' => ['recipientUsers' => ['data' => [['type' => 'users', 'id' => (string) $recipient]]]],
            ]],
        ]));
    }

    protected function setIgnored(int $actor, int $target, bool $ignored): ResponseInterface
    {
        return $this->send($this->request('PATCH', "/api/users/$target", [
            'authenticatedAs' => $actor,
            'json'            => ['data' => ['type' => 'users', 'id' => (string) $target, 'attributes' => ['ignored' => $ignored]]],
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function user(int $id, string $username): array
    {
        return ['id' => $id, 'username' => $username, 'email' => "$username@machine.local", 'is_email_confirmed' => 1];
    }
}
