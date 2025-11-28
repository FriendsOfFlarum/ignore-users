<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\User\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class IgnoredFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'ignored';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $this->constrain($state->getQuery(), $state->getActor(), $negate);
    }

    protected function constrain(Builder $query, User $actor, bool $negate): void
    {
        $method = $negate ? 'whereNotExists' : 'whereExists';

        $query->$method(
            function ($query) use ($actor) {
                $query->selectRaw('1')
                    ->from('ignored_user')
                    ->whereColumn('users.id', 'ignored_user_id')
                    ->where('user_id', $actor->id);
            }
        );
    }
}
