<?php

namespace FoF\IgnoreUsers;

use Flarum\Api\Controller\ListUsersController;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class PrepareIgnoredUsers
{
    public function __invoke(ListUsersController $controller, $data, ServerRequestInterface $request, Document $document)
    {
        /**
         * @var \Flarum\User\User
         */
        $actor = $request->getAttribute('actor');
        $actor->load('ignoredUsers');
    }
}
