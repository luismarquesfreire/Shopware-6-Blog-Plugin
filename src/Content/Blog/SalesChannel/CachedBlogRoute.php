<?php

declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SalesChannel;

use Sas\BlogModule\Content\Blog\Event\BlogRouteCacheKeyEvent;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductBoxStruct;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\Content\LandingPage\Event\LandingPageRouteCacheTagsEvent;
use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\Adapter\Cache\CacheCompressor;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @RouteScope(scopes={"store-api"})
 */
class CachedBlogRoute extends AbstractBlogRoute
{
    private AbstractBlogRoute $decorated;

    private TagAwareAdapterInterface $cache;

    private EntityCacheKeyGenerator $generator;

    /**
     * @var AbstractCacheTracer<LandingPageRouteResponse>
     */
    private AbstractCacheTracer $tracer;

    private array $states;

    private EventDispatcherInterface $dispatcher;

    private LoggerInterface $logger;

    /**
     * @param AbstractCacheTracer<LandingPageRouteResponse> $tracer
     */
    public function __construct(
        AbstractBlogRoute $decorated,
        TagAwareAdapterInterface $cache,
        EntityCacheKeyGenerator $generator,
        AbstractCacheTracer $tracer,
        EventDispatcherInterface $dispatcher,
        array $states,
        LoggerInterface $logger
    ) {
        $this->decorated = $decorated;
        $this->cache = $cache;
        $this->generator = $generator;
        $this->tracer = $tracer;
        $this->states = $states;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public static function buildName(string $id): string
    {
        return 'blog-route-' . $id;
    }

    public function getDecorated(): AbstractBlogRoute
    {
        return $this->decorated;
    }

    /**
     * @Route("/store-api/sas-blog/{blogEntriesId}", name="store-api.blog.detail", methods={"POST"})
     */
    public function load(string $blogEntriesId, Request $request, SalesChannelContext $context): BlogRouteResponse
    {
        if ($context->hasState(...$this->states)) {
            $this->logger->info('cache-miss: ' . self::buildName($blogEntriesId));

            return $this->getDecorated()->load($blogEntriesId, $request, $context);
        }

        $item = $this->cache->getItem(
            $this->generateKey($blogEntriesId, $request, $context)
        );

        try {
            if ($item->isHit() && $item->get()) {
                $this->logger->info('cache-hit: ' . self::buildName($blogEntriesId));

                return CacheCompressor::uncompress($item);
            }
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        $this->logger->info('cache-miss: ' . self::buildName($blogEntriesId));

        $name = self::buildName($blogEntriesId);
        $response = $this->tracer->trace($name, function () use ($blogEntriesId, $request, $context) {
            return $this->getDecorated()->load($blogEntriesId, $request, $context);
        });

        $item = CacheCompressor::compress($item, $response);

        $item->tag($this->generateTags($blogEntriesId, $response, $request, $context));

        $this->cache->save($item);

        return $response;
    }

    private function generateKey(string $blogEntriesId, Request $request, SalesChannelContext $context): string
    {
        $parts = array_merge(
            $request->query->all(),
            $request->request->all(),
            [
                self::buildName($blogEntriesId),
                $this->generator->getSalesChannelContextHash($context),
            ]
        );

        $event = new BlogRouteCacheKeyEvent($blogEntriesId, $parts, $request, $context, null);
        $this->dispatcher->dispatch($event);

        return md5(JsonFieldSerializer::encodeJson($event->getParts()));
    }

    private function generateTags(string $blogEntriesId, BlogRouteResponse $response, Request $request, SalesChannelContext $context): array
    {
        $tags = array_merge(
            $this->tracer->get(self::buildName($blogEntriesId)),
            $this->extractIds($response),
            [self::buildName($blogEntriesId)]
        );

        $event = new LandingPageRouteCacheTagsEvent($blogEntriesId, $tags, $request, $response, $context, null);
        $this->dispatcher->dispatch($event);

        return array_unique(array_filter($event->getTags()));
    }

    private function extractIds(BlogRouteResponse $response): array
    {
        $page = $response->getLandingPage()->getCmsPage();

        if ($page === null) {
            return [];
        }

        $ids = [];

        $slots = $page->getElementsOfType('product-slider');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $slider = $slot->getData();

            if (!$slider instanceof ProductSliderStruct) {
                continue;
            }

            if ($slider->getProducts() === null) {
                continue;
            }
            foreach ($slider->getProducts() as $product) {
                $ids[] = $product->getId();
                $ids[] = $product->getParentId();
            }
        }

        $slots = $page->getElementsOfType('product-box');
        /** @var CmsSlotEntity $slot */
        foreach ($slots as $slot) {
            $box = $slot->getData();

            if (!$box instanceof ProductBoxStruct) {
                continue;
            }
            if ($box->getProduct() === null) {
                continue;
            }

            $ids[] = $box->getProduct()->getId();
            $ids[] = $box->getProduct()->getParentId();
        }

        $ids = array_values(array_unique(array_filter($ids)));

        return array_merge(
            array_map([EntityCacheKeyGenerator::class, 'buildProductTag'], $ids),
            [EntityCacheKeyGenerator::buildCmsTag($page->getId())]
        );
    }
}
