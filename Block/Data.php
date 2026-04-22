<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\LlmTxt\Config\Backend\GeneratedContent;
use MageOS\LlmTxt\Service\LlmTxtProvider;

class Data extends AbstractBlock implements IdentityInterface
{
    public function __construct(
        private readonly LlmTxtProvider $llmTxtProvider,
        private readonly StoreManagerInterface $storeManager,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _toHtml(): string
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();

            return $this->llmTxtProvider->get($storeId) . PHP_EOL;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage(), ['exception' => $e]);

            return '';
        }
    }

    public function getIdentities(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();

        return [
            GeneratedContent::CACHE_TAG . '_' . $storeId,
        ];
    }
}
