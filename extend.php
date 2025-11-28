<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Search\UserSearcher;
use Flarum\User\User;
use FoF\IgnoreUsers\Event\Ignoring;
use FoF\IgnoreUsers\Event\Unignoring;
use FoF\IgnoreUsers\User\Search\Filter\IgnoredFilter;
use Illuminate\Contracts\Events\Dispatcher;

return [
    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less')
        ->route('/ignoredUsers', 'ignored.users.view'),

    (new Extend\Model(User::class))
        ->relationship('ignoredUsers', function (User $model) {
            return $model->belongsToMany(User::class, 'ignored_user', 'user_id', 'ignored_user_id')
            ->withPivot('ignored_at');
        })
        ->relationship('ignoredBy', function (User $model) {
            return $model->belongsToMany(User::class, 'ignored_user', 'ignored_user_id', 'user_id')
            ->withPivot('ignored_at');
        }),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Relationship\ToMany::make('ignoredUsers')
                ->type('users')
                ->includable(),

            Schema\Boolean::make('ignored')
                ->get(function (User $user, Context $context) {
                    $actor = $context->getActor();
                    $canIgnored = !$user->can('notBeIgnored');

                    /** @phpstan-ignore-next-line */
                    return $canIgnored && $actor->ignoredUsers->contains($user);
                })
                ->writable()
                ->set(function (User $user, bool $value, Context $context) {
                    $actor = $context->getActor();
                    $actor->assertCan('ignore', $user);

                    /** @phpstan-ignore-next-line */
                    $exists = $actor->ignoredUsers()->where('ignored_user_id', $user->id)->exists();
                    $changed = false;

                    if ($value) {
                        if (!$exists) {
                            resolve(Dispatcher::class)->dispatch(new Ignoring($user, $actor));
                            /** @phpstan-ignore-next-line */
                            $actor->ignoredUsers()->attach($user, ['ignored_at' => Carbon::now()]);
                            $changed = true;
                        }
                    } elseif ($exists) {
                        resolve(Dispatcher::class)->dispatch(new Unignoring($user, $actor));
                        /** @phpstan-ignore-next-line */
                        $actor->ignoredUsers()->detach($user);
                        $changed = true;
                    }

                    if ($changed) {
                        $actor->load('ignoredUsers');
                    }
                }),

            Schema\Boolean::make('canBeIgnored')
                ->get(function (User $user, $context) {
                    return (bool) $context->getActor()->can('ignore', $user);
                }),
        ])
        ->endpoint(['index', 'show'], function ($endpoint) {
            return $endpoint->addDefaultInclude(['ignoredUsers']);
        }),

    (new Extend\Policy())
        ->modelPolicy(User::class, Access\UserPolicy::class)
        ->modelPolicy(User::class, Access\ByobuPolicy::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(UserSearcher::class, IgnoredFilter::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->endpoint('show', fn ($endpoint) => $endpoint->addDefaultInclude(['actor.ignoredUsers'])),
];
