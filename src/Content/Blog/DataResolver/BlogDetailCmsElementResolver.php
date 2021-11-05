<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\DataResolver;

use Sas\BlogModule\Content\Blog\BlogEntriesDefinition;
use Sas\BlogModule\Content\Blog\BlogEntriesEntity;
use Sas\BlogModule\Content\Blog\BlogSeoUrlRoute;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BlogDetailCmsElementResolver extends AbstractCmsElementResolver
{

    /**
     * @var EntityRepositoryInterface
     */
    private $seoUrlRepository;

    public function __construct(EntityRepositoryInterface $seoUrlRepository)
    {
        $this->seoUrlRepository = $seoUrlRepository;
    }

    public function getType(): string
    {
        return 'blog-detail';
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        /* get the config from the element */
        $config = $slot->getFieldConfig();

        $id = $resolverContext->getRequest()->get('articleId') ?? ($resolverContext->getEntity() instanceof BlogEntriesEntity ? $resolverContext->getEntity()->get('id') : null);

        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('active', true),
            new EqualsFilter('id', $id)
        );
        $criteria->addAssociations(['author', 'blogCategories']);

        $criteriaCollection = new CriteriaCollection();

        $criteriaCollection->add(
            'sas_blog',
            BlogEntriesDefinition::class,
            $criteria
        );

        return $criteriaCollection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        if (!empty($result->get('sas_blog')->first())) {
            $blog = $result->get('sas_blog')->first();
            $blog->setSeoUrl($this->getCanonicalUrl($blog, $resolverContext->getSalesChannelContext()));
            $slot->setData($blog);
        }
    }

    private function getCanonicalUrl(BlogEntriesEntity $entry, SalesChannelContext $context): string
    {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('routeName', BlogSeoUrlRoute::ROUTE_NAME));
        $criteria->addFilter(new EqualsFilter('isCanonical', true));
        $criteria->addFilter(new EqualsAnyFilter('foreignKey', [$entry->getId()]));
        $criteria->addFilter(new EqualsFilter('salesChannelId', $context->getSalesChannelId()));
        $criteria->addFilter(new EqualsFilter('languageId', $context->getContext()->getLanguageId()));

        $result = $this->seoUrlRepository->search($criteria, $context->getContext());

        $pathByCategoryId = [];

        /** @var SeoUrlEntity $seoUrl */
        foreach ($result as $seoUrl) {
            // Map all urls to their corresponding category
            $pathByCategoryId[$seoUrl->getForeignKey()] = '/' . ($seoUrl->getSeoPathInfo() ?? $seoUrl->getPathInfo());
        }

        return sizeof($pathByCategoryId) > 0 ? array_values($pathByCategoryId)[0] : null;
    }
}
