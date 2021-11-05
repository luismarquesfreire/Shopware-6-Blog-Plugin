<?php

declare(strict_types=1);

namespace Sas\BlogModule\Pwa\PageResult\Blog;

use Shopware\Core\Content\Cms\CmsPageEntity;
use SwagShopwarePwa\Pwa\PageLoader\Context\PageLoaderContext;
use SwagShopwarePwa\Pwa\PageResult\AbstractPageResultHydrator;

class BlogPageResultHydrator extends AbstractPageResultHydrator
{
    public function hydrate(PageLoaderContext $pageLoaderContext, ?CmsPageEntity $cmsPageEntity): BlogPageResult
    {
        $pageResult = new BlogPageResult();

        $pageResult->setCmsPage($cmsPageEntity);
        $pageResult->setResourceType($pageLoaderContext->getResourceType());
        $pageResult->setResourceIdentifier($pageLoaderContext->getResourceIdentifier());

        return $pageResult;
    }
}
