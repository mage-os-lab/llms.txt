<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Controller\Adminhtml\Generate;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\LlmTxt\Model\Generator;
use MageOS\LlmTxt\Model\OpenAi\Client as OpenAiClient;
use MageOS\LlmTxt\Model\StoreDataCollector;

class Index implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_LlmTxt::config';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly OpenAiClient $openAiClient,
        private readonly StoreDataCollector $storeDataCollector,
        private readonly Generator $generator,
        private readonly StoreManagerInterface $storeManager
    ) {}

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $storeId = (int) $this->request->getParam('store', 0);
            if ($storeId === 0) {
                $storeId = (int) $this->storeManager->getDefaultStoreView()->getId();
            }

            // Collect store data
            $storeData = $this->storeDataCollector->collect($storeId);

            // Generate via OpenAI
            $generatedContent = $this->openAiClient->generateLlmsTxt($storeData, $storeId);

            // Estimate tokens
            $tokenCount = $this->generator->estimateTokenCount($generatedContent);

            return $result->setData([
                'success' => true,
                'content' => $generatedContent,
                'tokens' => $tokenCount,
                'message' => __('Content generated successfully! Token count: %1', $tokenCount)
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => __('Generation failed: %1', $e->getMessage())
            ]);
        }
    }
}
