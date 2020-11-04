<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Event\ConfigureUserGambits;
use Flarum\Extend;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use FoF\IgnoreUsers\Access;
use FoF\IgnoreUsers\Listener;
use Illuminate\Contracts\Events\Dispatcher;

return [
  new Extend\Locales(__DIR__ . '/resources/locale'),

  (new Extend\Frontend('admin'))
    ->js(__DIR__ . '/js/dist/admin.js'),

  (new Extend\Frontend('forum'))
    ->js(__DIR__ . '/js/dist/forum.js')
    ->css(__DIR__ . '/resources/less/forum.less')
    ->route('/ignoredUsers', 'ignored.users.view'),

  (new Extend\Model(User::class))
    ->belongsToMany('ignoredUsers',User::class, 'ignored_user', 'user_id', 'ignored_user_id'),

  (new Extend\Model(User::class))
    ->belongsToMany('ignoredBy', User::class, 'ignored_user', 'ignored_user_id', 'user_id'),

  function (Dispatcher $events) {
    $events->subscribe(Listener\AddIgnoredUsersRelationship::class);
    $events->listen(ConfigureUserGambits::class, Listener\AddIgnoredUserGambit::class);
    $events->listen(Saving::class, Listener\SaveIgnoredToDatabase::class);

    $events->subscribe(Listener\AddByobuDMPrevention::class);
    $events->subscribe(Access\UserPolicy::class);
    $events->subscribe(Access\ByobuPolicy::class);
  },
];
