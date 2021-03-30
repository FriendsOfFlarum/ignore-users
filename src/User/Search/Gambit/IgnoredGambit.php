<?php

/*
 * This file is part of fof/ignore-users.
 *
 * Copyright (c) FriendsOfFlarum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FoF\IgnoreUsers\User\Search\Gambit;

use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use Flarum\User\Search\UserSearch;
use LogicException;

class IgnoredGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'is:ignor(?:ing|ed)';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (!$search instanceof UserSearch) {
            throw new LogicException('This gambit can only be applied on a UserSearch');
        }

        $actor = $search->getActor();

        $method = $negate ? 'whereNotExists' : 'whereExists';

        $search->getQuery()->$method(
            function ($query) use ($actor) {
                $query->selectRaw('1')
                    ->from('ignored_user')
                    ->whereColumn('users.id', 'ignored_user_id')
                    ->where('user_id', $actor->id);
            }
        );
    }
}
