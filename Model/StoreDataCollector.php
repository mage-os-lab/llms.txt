<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class StoreDataCollector
{
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly PageCollectionFactory $pageCollectionFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Config $config,
    ) {}

    public function collect(int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);
        $storeLocale = (string)$this->scopeConfig->getValue('general/locale/code');

        return [
            'store_name' => $store->getName(),
            'store_url' => $store->getBaseUrl(),
            'store_locale' => $storeLocale,
            'categories' => $this->collectCategories($storeId),
            'products' => $this->collectProducts($storeId),
            'cms_pages' => $this->collectCmsPages($storeId),
        ];
    }

    private function collectCategories(int $storeId): array
    {
        $categoryIds = $this->config->getCategoryIds($storeId);
        if (!$categoryIds) {
            return [];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'url_key', 'description'])
            ->addAttributeToFilter('entity_id', ['in' => $categoryIds])
            ->setOrder('position', 'ASC');

        $categories = [];
        /** @var Category $category */
        foreach ($collection as $category) {
            $categories[] = [
                'name' => (string) $category->getName(),
                'url' => $category->getUrl(),
                'description' => strip_tags((string) $category->getDescription()),
            ];
        }

        return $categories;
    }

    private function collectProducts(int $storeId): array
    {
        $productSkus = $this->config->getProductSkus($storeId);
        if (!$productSkus) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'url_key', 'short_description', 'sku'])
            ->addAttributeToFilter('sku', ['in' => $productSkus])
            ->setOrder('created_at', 'DESC');

        $products = [];
        /** @var Product $product */
        foreach ($collection as $product) {
            $products[] = [
                'name' => (string) $product->getName(),
                'url' => $product->getProductUrl(),
                'description' => strip_tags((string) $product->getShortDescription()),
            ];
        }

        return $products;
    }

    private function collectCmsPages(int $storeId): array
    {
        $pageIdentifiers = $this->config->getCmsPageIdentifiers($storeId);
        if (!$pageIdentifiers) {
            return [];
        }

        $collection = $this->pageCollectionFactory->create();
        $collection->addFieldToSelect(['identifier', 'title'])
            ->addFieldToFilter('identifier', ['in' => $pageIdentifiers])
            ->addStoreFilter($storeId)
            ->setOrder('creation_time', 'DESC');

        $pages = [];
        /** @var Page $page */
        foreach ($collection as $page) {
            $identifier = (string) $page->getIdentifier();

            $pages[] = [
                'title' => (string) $page->getTitle(),
                'url' => $this->urlBuilder->getUrl(null, ['_direct' => $page->getIdentifier()]),
                'identifier' => $identifier,
            ];
        }

        return $pages;
    }
}
