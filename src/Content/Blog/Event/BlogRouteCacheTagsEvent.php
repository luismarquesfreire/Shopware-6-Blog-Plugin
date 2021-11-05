<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\Event;

use Shopware\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Symfony\Component\HttpFoundation\Request;

class BlogRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
    protected string $blogEntriesId;

    public function __construct(string $blogEntriesId, array $tags, Request $request, StoreApiResponse $response, SalesChannelContext $context, ?Criteria $criteria)
    {
        parent::__construct($tags, $request, $response, $context, $criteria);
        $this->blogEntriesId = $blogEntriesId;
    }

    public function getLandingPageId(): string
    {
        return $this->blogEntriesId;
    }
}
