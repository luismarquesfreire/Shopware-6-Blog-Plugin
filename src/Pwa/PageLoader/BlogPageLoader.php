<?php

declare(strict_types=1);

namespace Sas\BlogModule\Pwa\PageLoader;

use Sas\BlogModule\Content\Blog\SalesChannel\AbstractBlogRoute;
use Sas\BlogModule\Pwa\PageResult\Blog\BlogPageResult;
use SwagShopwarePwa\Pwa\PageLoader\Context\PageLoaderContext;
use SwagShopwarePwa\Pwa\PageLoader\PageLoaderInterface;
use Sas\BlogModule\Pwa\PageResult\Blog\BlogPageResultHydrator;

/**
 * This class loads a static landing page. Landing pages behave the same way as CMS pages, but do not have a breadcrumb.
 *
 * @package SwagShopwarePwa\Pwa\PageLoader
 */
class BlogPageLoader implements PageLoaderInterface
{
    private const RESOURCE_TYPE = 'sas.frontend.blog.detail';

    /**
     * @var BlogRoute
     */
    private $blogRoute;

    /**
     * @var BlogPageResultHydrator
     */
    private $resultHydrator;

    public function __construct(AbstractBlogRoute $blogRoute, BlogPageResultHydrator $resultHydrator)
    {
        $this->blogRoute = $blogRoute;
        $this->resultHydrator = $resultHydrator;
    }

    public function getResourceType(): string
    {
        return self::RESOURCE_TYPE;
    }

    /**
     * @param PageLoaderContext $pageLoaderContext
     *
     * @return BlogPageResult
     */
    public function load(PageLoaderContext $pageLoaderContext): BlogPageResult
    {
        $blogResult = $this->blogRoute->load(
            $pageLoaderContext->getResourceIdentifier(),
            $pageLoaderContext->getRequest(),
            $pageLoaderContext->getContext()
        );

        $pageResult = $this->resultHydrator->hydrate(
            $pageLoaderContext,
            $blogResult->getBlogEntry()->getCmsPage() ?? null
        );

        return $pageResult;
    }
}
