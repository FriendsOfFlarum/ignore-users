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

class IgnoringTest extends IgnoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-ignore-users');

        $this->seedUsers();
    }

    private function ignoredInResponse(int $actor, int $target, bool $ignored): bool
    {
        $response = $this->setIgnored($actor, $target, $ignored);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return json_decode((string) $response->getBody(), true)['data']['attributes']['ignored'];
    }

    #[Test]
    public function ignoring_a_user_responds_with_them_ignored(): void
    {
        $this->assertTrue($this->ignoredInResponse(4, 2, true));
    }

    #[Test]
    public function unignoring_a_user_responds_with_them_no_longer_ignored(): void
    {
        $this->assertFalse($this->ignoredInResponse(3, 2, false));
    }
}
