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

use Flarum\Event\ConfigureUserGambits;
use FoF\IgnoreUsers\User\Search\Gambit\IgnoredGambit;

class AddIgnoredUserGambit
{
    public function handle(ConfigureUserGambits $event)
    {
        $event->gambits->add(IgnoredGambit::class);
    }
}
