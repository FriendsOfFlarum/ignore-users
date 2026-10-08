<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Listener;

use Flarum\Discussion\Event\Saving;
use Flarum\User\User;
use Illuminate\Support\Arr;

class PreventPrivateDiscussionWithIgnoringUsers
{
    public function handle(Saving $event): void
    {
        /** @var array<int, array{id: int|string}> $data */
        $data = Arr::get($event->data, 'relationships.recipientUsers.data', []);
        $requested = collect(array_map(fn (array $recipient) => (int) $recipient['id'], $data));

        if ($requested->isEmpty()) {
            return;
        }

        // Read from the database: by Saving time the in-memory recipientUsers relation already holds the payload,
        // so it cannot tell new recipients from existing ones (which Screener::added() relies on).
        $existing = $event->discussion->exists
            /** @phpstan-ignore-next-line */
            ? $event->discussion->recipientUsers()->pluck('users.id')->map(fn ($id) => (int) $id)
            : collect();

        $added = $requested->diff($existing)->reject(fn (int $id) => $id === $event->actor->id);

        // Only newly added recipients are checked, so existing conversations are not broken retroactively.
        foreach (User::query()->whereIn('id', $added)->get() as $recipient) {
            $event->actor->assertCan('sendDirectMessage', $recipient);
        }
    }
}
