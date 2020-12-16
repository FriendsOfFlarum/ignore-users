<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) 2019 FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers;

use Flarum\Api\Controller\ListUsersController;
use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Api\Controller\ShowUserController;
use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Database\AbstractModel;
use Flarum\Extend;
use FoF\IgnoreUsers\Listener;
use FoF\IgnoreUsers\Access;
use Flarum\Event\ConfigureUserGambits;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\User\Event\Saving;
use Flarum\User\User;

return [
    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less')
        ->route('/ignoredUsers', 'ignored.users.view'),

    (new Extend\Model(User::class))
        ->relationship('ignoredUsers', function (AbstractModel $model) {
          return $model->belongsToMany(User::class, 'ignored_user', 'user_id', 'ignored_user_id')
            ->withPivot('ignored_at');
        })
        ->relationship('ignoredBy', function (AbstractModel $model) {
          return $model->belongsToMany(User::class, 'ignored_user', 'ignored_user_id', 'user_id')
            ->withPivot('ignored_at');
        }),

    (new Extend\ApiSerializer(CurrentUserSerializer::class))
        ->hasMany('ignoredUsers', UserSerializer::class),

    (new Extend\ApiController(ListUsersController::class))
        ->prepareDataForSerialization(PrepareIgnoredUsers::class)
        ->addInclude('ignoredUsers'),

    (new Extend\ApiController(ShowUserController::class))
        ->addInclude('ignoredUsers'),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->attribute('ignored', function (UserSerializer $serializer, User $user){
            $canIgnored = !$user->can('notBeIgnored');
            return $canIgnored && $serializer->getActor()->ignoredUsers->contains($user);
        }),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->mutate(function (ForumSerializer $serializer) {
            $attributes['byobu-extend'] = true;
            return $attributes;
        }),

    (new Extend\Policy())
        ->modelPolicy(User::class, Access\UserPolicy::class)
        ->modelPolicy(User::class, Access\ByobuPolicy::class),

    (new Extend\Event())
        ->listen(Saving::class, Listener\SaveIgnoredToDatabase::class)
        ->listen(ConfigureUserGambits::class, Listener\AddIgnoredUserGambit::class),

    function (Dispatcher $events) {
        $events->subscribe(Listener\AddByobuDMPrevention::class);
    },
];
