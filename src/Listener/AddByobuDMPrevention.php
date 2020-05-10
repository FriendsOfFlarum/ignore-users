<?php
/**
 *
 *  This file is part of fof/ignore-users.
 *
 *  Copyright (c) 2019 FriendsOfFlarum..
 *
 *  For the full copyright and license information, please view the license.md
 *  file that was distributed with this source code.
 *
 */

namespace FoF\IgnoreUsers\Listener;

use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\UserRepository;
use FoF\Byobu\Events\DiscussionMadePrivate;
use FoF\Byobu\Events\SearchingForRecipients;
use Illuminate\Contracts\Events\Dispatcher;

class AddByobuDMPrevention
{

    /**
     * @var UserRepository
     */
    protected $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(DiscussionMadePrivate::class, [$this, 'preventNewDM']);
        $events->listen(SearchingForRecipients::class, [$this, 'preventRecipientSearch']);
    }

    public function preventRecipientSearch(SearchingForRecipients $event)
    {
        $actor = $event->search->getActor();

        $ids = $actor->ignoredBy()->pluck('id')->all();

        $event->search->getQuery()->whereNotIn('id', $ids);
    }

    public function preventNewDM(DiscussionMadePrivate $event)
    {
        if (array_intersect($event->newUsers->toArray(), $event->actor->ignoredBy()->pluck('id')->all())) {
            $event->discussion->delete();
            throw new PermissionDeniedException();
        }
    }
}