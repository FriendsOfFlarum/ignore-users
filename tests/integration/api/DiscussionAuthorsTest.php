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
use Flarum\Post\Post;
use FoF\IgnoreUsers\Tests\integration\IgnoreTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The forum collapses posts by authors the viewer ignores, using the `ignored`
 * flag each author carries when a discussion is loaded.
 */
class DiscussionAuthorsTest extends IgnoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-ignore-users');

        $this->seedUsers();

        $post = fn (int $number, int $user) => ['id' => $number, 'discussion_id' => 1, 'number' => $number, 'user_id' => $user, 'type' => 'comment', 'content' => '<t>Hello</t>', 'created_at' => '2026-01-01 00:00:00'];

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'A discussion', 'created_at' => '2026-01-01 00:00:00', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 3, 'last_post_number' => 3],
            ],
            Post::class => [$post(1, 2), $post(2, 4), $post(3, 5)],
        ]);
    }

    #[Test]
    public function loading_a_discussions_posts_marks_the_authors_the_viewer_ignores(): void
    {
        // The discussion page loads its posts, with their authors, through this request.
        $response = $this->send($this->request('GET', '/api/posts', ['authenticatedAs' => 3])
            ->withQueryParams(['filter' => ['discussion' => 1], 'page' => ['near' => 1]]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $ignored = [];

        foreach (json_decode((string) $response->getBody(), true)['included'] ?? [] as $resource) {
            if ($resource['type'] === 'users') {
                $ignored[(int) $resource['id']] = $resource['attributes']['ignored'];
            }
        }

        ksort($ignored);

        // 3 ignores 2 and 5, but 5 can't be ignored; 4 isn't ignored.
        $this->assertSame([2 => true, 4 => false, 5 => false], array_intersect_key($ignored, [2 => 0, 4 => 0, 5 => 0]));
    }
}
