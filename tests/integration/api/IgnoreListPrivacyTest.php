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

    #[Test]
    public function listing_users_reads_only_the_viewers_own_ignore_list(): void
    {
        $this->prepareDatabase(['group_permission' => [
            ['group_id' => Group::MEMBER_ID, 'permission' => 'viewUserList'],
        ]]);

        $this->app();
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send($this->request('GET', '/api/users', ['authenticatedAs' => 4]));

        $ignoreListReads = array_filter($db->getQueryLog(), fn (array $query) => str_contains($query['query'], 'ignored_user'));
        $db->disableQueryLog();

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(1, $ignoreListReads);
    }
}
