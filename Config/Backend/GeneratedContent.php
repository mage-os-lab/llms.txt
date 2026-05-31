<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class GeneratedContent extends Value implements IdentityInterface
{
    public const CACHE_TAG = 'llmtxt';

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly StoreManagerInterface $storeManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function getIdentities(): array
    {
        if ($this->getScope() === ScopeInterface::SCOPE_STORES) {
            return [self::CACHE_TAG . '_' . $this->getScopeId()];
        }

        $tags = [];
        foreach ($this->storeManager->getStores() as $store) {
            $tags[] = self::CACHE_TAG . '_' . $store->getId();
        }

        return $tags;
    }

    public function afterSave(): self
    {
        $this->_eventManager->dispatch('clean_cache_by_tags', ['object' => $this]);

        return parent::afterSave();
    }
}
