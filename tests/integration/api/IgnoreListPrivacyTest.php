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

use FoF\IgnoreUsers\Tests\integration\IgnoreTestCase;
use PHPUnit\Framework\Attributes\Test;

class IgnoreListPrivacyTest extends IgnoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-ignore-users');

        $this->seedUsers();
    }

    #[Test]
    public function another_users_ignore_list_cannot_be_included(): void
    {
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 4])
            ->withQueryParams(['include' => 'ignoredUsers']));

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertArrayNotHasKey('ignoredUsers', $body['data']['relationships'] ?? []);
        $this->assertArrayNotHasKey('included', $body);
    }
}
