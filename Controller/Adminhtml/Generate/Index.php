<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Controller\Adminhtml\Generate;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\LlmTxt\Service\LlmTxtGenerator;

class Index implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_LlmTxt::config';

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LlmTxtGenerator $llmTxtGenerator,
    ) {}

    private function resolveApiKeyOverride(): ?string
    {
        $posted = (string) $this->request->getParam('api_key', '');

        // Obscure fields post back all-asterisks when the user did not edit the field —
        // treat that as "use the saved key" by falling through to the encrypted config.
        if ($posted === '' || preg_match('/^\*+$/', $posted) === 1) {
            return null;
        }

        return $posted;
    }

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        try {
            $storeId = (int) $this->request->getParam('store', 0);
            if ($storeId === 0) {
                $storeId = (int) $this->storeManager->getDefaultStoreView()->getId();
            }

            $apiKeyOverride = $this->resolveApiKeyOverride();

            $generatedContent = $this->llmTxtGenerator->generateLlmTxt($storeId, $apiKeyOverride);
            $tokenCount = $this->llmTxtGenerator->estimateTokenCount($generatedContent);

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
