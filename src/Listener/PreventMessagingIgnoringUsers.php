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

use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage\Event\Creating;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class PreventMessagingIgnoringUsers
{
    public function handle(Creating $event): void
    {
        $actor = User::find($event->message->user_id);

        if (!$actor) {
            return;
        }

        foreach ($this->recipients($event, $actor) as $recipient) {
            $actor->assertCan('sendDirectMessage', $recipient);
        }
    }

    /**
     * @return Collection<int, User>
     */
    protected function recipients(Creating $event, User $actor): Collection
    {
        if ($event->message->dialog_id) {
            /** @var Collection<int, User> */
            return Dialog::query()->findOrFail($event->message->dialog_id)
                ->users()->where('users.id', '!=', $actor->id)->get();
        }

        $ids = array_filter(Arr::pluck(Arr::get($event->data, 'attributes.users', []), 'id'));

        /** @var Collection<int, User> */
        return User::query()->whereIn('id', $ids)->where('id', '!=', $actor->id)->get();
    }
}
