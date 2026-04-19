<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use MageOS\LlmTxt\Service\CsvSerializer;

class Config
{
    public const XML_PATH_ENABLED = 'llmtxt/general/enabled';
    public const XML_PATH_SITE_NAME = 'llmtxt/ai_generation/site_name';
    public const XML_PATH_SITE_DESCRIPTION = 'llmtxt/ai_generation/site_description';
    public const XML_PATH_ADDITIONAL_CONTENT = 'llmtxt/ai_generation/additional_content';
    public const XML_PATH_GENERATED_CONTENT = 'llmtxt/content/generated_content';
    public const XML_PATH_OPENAI_API_KEY = 'llmtxt/openai/openai_api_key';
    public const XML_PATH_OPENAI_MODEL = 'llmtxt/openai/openai_model';
    public const XML_PATH_CATEGORY_IDS = 'llmtxt/ai_generation/category_ids';
    public const XML_PATH_PRODUCT_SKUS = 'llmtxt/ai_generation/product_skus';
    public const XML_PATH_CMS_PAGE_IDENTIFIERS = 'llmtxt/ai_generation/cms_page_identifiers';
    public const XML_PATH_LOG_PROMPT = 'llmtxt/ai_generation/log_prompt';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly CsvSerializer $csvSerializer,
    ) {}

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSiteName(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SITE_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getSiteDescription(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SITE_DESCRIPTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAdditionalContent(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ADDITIONAL_CONTENT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGeneratedContent(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_GENERATED_CONTENT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getOpenAiApiKey(?int $storeId = null): string
    {
        $encrypted = (string) $this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $encrypted ? $this->encryptor->decrypt($encrypted) : '';
    }

    public function getOpenAiModel(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_MODEL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCategoryIds(?int $storeId = null): array
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_IDS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return array_map('intval', $this->csvSerializer->unserialize($value));
    }

    public function getProductSkus(?int $storeId = null): array
    {
        return $this->csvSerializer->unserialize(
            (string) $this->scopeConfig->getValue(
                self::XML_PATH_PRODUCT_SKUS,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
    }

    public function isLogPromptEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LOG_PROMPT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCmsPageIdentifiers(?int $storeId = null): array
    {
        return $this->csvSerializer->unserialize(
            (string) $this->scopeConfig->getValue(
                self::XML_PATH_CMS_PAGE_IDENTIFIERS,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
    }
}
