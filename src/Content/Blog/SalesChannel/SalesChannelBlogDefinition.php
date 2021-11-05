<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SalesChannel;

use Sas\BlogModule\Content\Blog\BlogEntriesDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelDefinitionInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SalesChannelBlogDefinition extends BlogEntriesDefinition implements SalesChannelDefinitionInterface
{
    public function processCriteria(Criteria $criteria, SalesChannelContext $context): void
    {
    }
}
