<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SalesChannel;

use Sas\BlogModule\Content\Blog\BlogEntriesDefinition;
use Sas\BlogModule\Content\Blog\BlogEntriesEntity;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\Exception\PageNotFoundException;
use Shopware\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Shopware\Core\Content\LandingPage\LandingPageDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Storefront\Page\Navigation\NavigationPage;

/**
 * @RouteScope(scopes={"store-api"})
 */
class BlogRoute extends AbstractBlogRoute
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var GenericPageLoaderInterface
     */
    private $genericPageLoader;

    /**
     * @var EntityRepositoryInterface
     */
    private $blogEntriesRepository;

    /**
     * @var SalesChannelCmsPageLoaderInterface
     */
    private $cmsPageLoader;

    /**
     * @var LandingPageDefinition
     */
    private $blogEntriesDefinition;

    public function __construct(
        SystemConfigService $systemConfigService,
        GenericPageLoaderInterface $genericPageLoader,
        EntityRepositoryInterface $blogEntriesRepository,
        SalesChannelCmsPageLoaderInterface $cmsPageLoader,
        BlogEntriesDefinition $blogEntriesDefinition
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->genericPageLoader = $genericPageLoader;
        $this->blogEntriesRepository = $blogEntriesRepository;
        $this->cmsPageLoader = $cmsPageLoader;
        $this->blogEntriesDefinition = $blogEntriesDefinition;
    }

    public function getDecorated(): AbstractBlogRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Route("/store-api/blog/{blogEntriesId}", name="store-api.blog.detail", methods={"POST"})
     */
    public function load(string $blogEntriesId, Request $request, SalesChannelContext $context): BlogRouteResponse
    {
        $entry = $this->loadBlogEntry($blogEntriesId, $context);

        $page = $this->genericPageLoader->load($request, $context);
        $page = NavigationPage::createFrom($page);

        // $pageId = $entry->getCmsPageId();

        // if (!$pageId) {
        //     return new BlogRouteResponse($entry);
        // }

        $resolverContext = new EntityResolverContext($context, $request, $this->blogEntriesDefinition, $entry);

        $pages = $this->cmsPageLoader->load(
            $request,
            $this->createCriteria($request),
            $context,
            $entry->getTranslation('slotConfig'),
            $resolverContext
        );

        // if (!$pages->has($pageId)) {
        //     throw new PageNotFoundException($pageId);
        // }

        $page->setCmsPage($pages->first());
        $metaInformation = $page->getMetaInformation();

        if ($entry->getAuthor()) {
            $metaInformation->setAuthor($entry->getAuthor()->getTranslated()['name']);
        }

        $page->setMetaInformation($metaInformation);

        $page->setNavigationId($page->getHeader()->getNavigation()->getActive()->getId());

        $entry->setCmsPage($pages->first());

        return new BlogRouteResponse($entry);
    }

    private function loadBlogEntry(string $blogEntriesId, SalesChannelContext $context): BlogEntriesEntity
    {
        $criteria = new Criteria([$blogEntriesId]);
        $criteria->setTitle('sas-blog::data');

        $criteria->addFilter(new EqualsFilter('active', true));
        // $criteria->addFilter(new EqualsFilter('salesChannels.id', $context->getSalesChannel()->getId()));

        $entry = $this->blogEntriesRepository
            ->search($criteria, $context->getContext())
            ->get($blogEntriesId);

        if (!$entry) {
            throw new PageNotFoundException($blogEntriesId);
        }

        return $entry;
    }

    private function createCriteria(Request $request): Criteria
    {

        $criteria = new Criteria([$this->systemConfigService->get('SasBlogModule.config.cmsBlogDetailPage')]);
        $criteria->setTitle('sas-blog::cms-page');

        $slots = $request->get('slots');

        if (\is_string($slots)) {
            $slots = explode('|', $slots);
        }

        if (!empty($slots) && \is_array($slots)) {
            $criteria
                ->getAssociation('sections.blocks')
                ->addFilter(new EqualsAnyFilter('slots.id', $slots));
        }

        return $criteria;
    }
}
