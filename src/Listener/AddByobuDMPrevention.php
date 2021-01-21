<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) 2019 - 2021 FriendsOfFlarum..
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\Listener;

use FoF\Byobu\Events\SearchingRecipient;

class AddByobuDMPrevention
{
    public function handle(SearchingRecipient $event)
    {
        $actor = $event->search->getActor();

        $ids = $actor->ignoredBy()->pluck('id')->all();

        $event->search->getQuery()->whereNotIn('id', $ids);
    }
}
