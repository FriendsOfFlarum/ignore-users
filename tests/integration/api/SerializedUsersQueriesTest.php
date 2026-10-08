<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\User;
use FoF\IgnoreUsers\Tests\integration\IgnoreTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every serialized user carries `ignored` and `canBeIgnored`. Working them out
 * shouldn't cost queries for each user on the page: here, the editors of a
 * discussion's posts, reached through a relation nothing loads their groups for.
 */
class SerializedUsersQueriesTest extends IgnoreTestCase
{
    private const EDITORS = [7, 8, 9, 10, 11, 12];

    // Ignored by the viewer (user 2).
    private const IGNORED_EDITOR = 8;

    // Holds `notBeIgnored` (seeded via group 10), and has a leftover ignore row from the viewer.
    private const EXEMPT_EDITOR = 5;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-ignore-users');

        $this->seedUsers();

        $users = [];
        $posts = [];

        foreach (self::EDITORS as $editor) {
            $users[] = ['id' => $editor, 'username' => "editor$editor", 'email' => "editor$editor@machine.local", 'is_email_confirmed' => 1];
        }

        foreach ([...self::EDITORS, self::EXEMPT_EDITOR, 1] as $i => $editor) {
            $posts[] = [
                'id' => $i + 1, 'number' => $i + 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment',
                'content' => '<t><p>Post</p></t>', 'created_at' => Carbon::parse('2026-03-01'),
                'edited_at' => Carbon::parse('2026-03-02'), 'edited_user_id' => $editor,
            ];
        }

        $this->prepareDatabase([
            User::class => $users,
            Discussion::class => [
                ['id' => 1, 'title' => 'Edited posts', 'created_at' => Carbon::parse('2026-03-01'), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => count($posts)],
            ],
            Post::class => $posts,
            'ignored_user' => [
                ['user_id' => 2, 'ignored_user_id' => self::IGNORED_EDITOR, 'ignored_at' => '2026-01-01 00:00:00'],
                ['user_id' => 2, 'ignored_user_id' => self::EXEMPT_EDITOR, 'ignored_at' => '2026-01-01 00:00:00'],
            ],
        ]);
    }

    public static function viewers(): array
    {
        return ['guest' => [null], 'member' => [2]];
    }

    #[Test]
    #[DataProvider('viewers')]
    public function serializing_users_does_not_query_once_per_user(?int $viewer): void
    {
        $queries = $this->listPostsWithEditors($viewer)['queries'];

        // A query bound to a single editor's id is that editor's own lookup.
        $perEditor = array_filter(
            $queries,
            fn (array $query) => count($query['bindings']) <= 2
                && array_intersect(array_filter($query['bindings'], 'is_numeric'), self::EDITORS)
        );

        $this->assertSame([], array_count_values(array_column($perEditor, 'query')), 'Queries were run for each editor separately.');
    }

    #[Test]
    public function a_member_sees_whom_they_ignore_and_whom_they_can_ignore(): void
    {
        $users = $this->listPostsWithEditors(2)['users'];

        foreach (self::EDITORS as $editor) {
            $this->assertSame($editor === self::IGNORED_EDITOR, $users[$editor]['ignored'], "Whether editor $editor is ignored.");
            $this->assertTrue($users[$editor]['canBeIgnored'], "Editor $editor can be ignored.");
        }

        // An exempt user's leftover ignore row doesn't count.
        $this->assertFalse($users[self::EXEMPT_EDITOR]['ignored']);
        $this->assertFalse($users[self::EXEMPT_EDITOR]['canBeIgnored']);
        $this->assertFalse($users[1]['ignored']);
        $this->assertFalse($users[1]['canBeIgnored'], 'An admin can\'t be ignored.');
    }

    #[Test]
    public function a_guest_ignores_no_one_and_can_ignore_no_one(): void
    {
        $users = $this->listPostsWithEditors(null)['users'];

        foreach ([...self::EDITORS, self::EXEMPT_EDITOR, 1] as $editor) {
            $this->assertFalse($users[$editor]['ignored']);
            $this->assertFalse($users[$editor]['canBeIgnored']);
        }
    }

    /**
     * @return array{queries: array<array{query: string, bindings: array}>, users: array<int, array<string, mixed>>}
     */
    private function listPostsWithEditors(?int $viewer): array
    {
        $this->app();

        $database = $this->database();
        $database->flushQueryLog();
        $database->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => $viewer])
                ->withQueryParams(['filter' => ['discussion' => 1], 'include' => 'editedUser'])
        );

        $queries = $database->getQueryLog();
        $database->disableQueryLog();

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $users = [];

        foreach (json_decode((string) $response->getBody(), true)['included'] ?? [] as $resource) {
            if ($resource['type'] === 'users') {
                $users[(int) $resource['id']] = $resource['attributes'];
            }
        }

        return ['queries' => $queries, 'users' => $users];
    }
}
