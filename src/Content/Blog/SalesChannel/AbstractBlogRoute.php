<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SalesChannel;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractBlogRoute
{
    abstract public function getDecorated(): AbstractBlogRoute;

    abstract public function load(string $blogId, Request $request, SalesChannelContext $context): BlogRouteResponse;
}
