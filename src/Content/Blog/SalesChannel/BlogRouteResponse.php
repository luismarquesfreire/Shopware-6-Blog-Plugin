<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SalesChannel;

use Sas\BlogModule\Content\Blog\BlogEntriesEntity;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

class BlogRouteResponse extends StoreApiResponse
{
    /**
     * @var BlogEntriesEntity
     */
    protected $object;

    public function __construct(BlogEntriesEntity $blog)
    {
        parent::__construct($blog);
    }

    public function getBlogEntry(): BlogEntriesEntity
    {
        return $this->object;
    }
}
