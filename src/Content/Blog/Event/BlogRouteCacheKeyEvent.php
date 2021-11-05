<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\Event;

use Shopware\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class BlogRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
    protected string $blogEntriesId;

    public function __construct(string $blogEntriesId, array $parts, Request $request, SalesChannelContext $context, ?Criteria $criteria)
    {
        parent::__construct($parts, $request, $context, $criteria);
        $this->blogEntriesId = $blogEntriesId;
    }

    public function getLandingPageId(): string
    {
        return $this->blogEntriesId;
    }
}
