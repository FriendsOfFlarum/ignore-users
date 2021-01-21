<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) 2019 - 2021 FriendsOfFlarum..
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers;

use Flarum\Api\Controller;
use Flarum\Api\Serializer;
use Flarum\Database\AbstractModel;
use Flarum\Event\ConfigureUserGambits;
use Flarum\Extend;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use FoF\Byobu\Events\SearchingRecipient;

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

    (new Extend\ApiSerializer(Serializer\CurrentUserSerializer::class))
        ->hasMany('ignoredUsers', Serializer\UserSerializer::class),

    (new Extend\ApiController(Controller\ListUsersController::class))
        ->prepareDataForSerialization(PrepareIgnoredUsers::class)
        ->addInclude('ignoredUsers'),

    (new Extend\ApiController(Controller\ShowUserController::class))
        ->addInclude('ignoredUsers'),

    (new Extend\ApiSerializer(Serializer\UserSerializer::class))
        ->attribute('ignored', function (Serializer\UserSerializer $serializer, User $user) {
            $canIgnored = !$user->can('notBeIgnored');

            return $canIgnored && $serializer->getActor()->ignoredUsers->contains($user);
        })
        ->attribute('canBeIgnored', function (Serializer\UserSerializer $serializer, User $user) {
            return (bool) $serializer->getActor()->can('ignore', $user);
        }),

    (new Extend\Policy())
        ->modelPolicy(User::class, Access\UserPolicy::class)
        ->modelPolicy(User::class, Access\ByobuPolicy::class),

    (new Extend\Event())
        ->listen(Saving::class, Listener\SaveIgnoredToDatabase::class)
        ->listen(ConfigureUserGambits::class, Listener\AddIgnoredUserGambit::class)
        ->listen(SearchingRecipient::class, Listener\AddByobuDMPrevention::class),
];
