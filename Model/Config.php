<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'llmtxt/general/enabled';
    public const XML_PATH_CACHE_LIFETIME = 'llmtxt/general/cache_lifetime';
    public const XML_PATH_SITE_NAME = 'llmtxt/general/site_name';
    public const XML_PATH_SITE_DESCRIPTION = 'llmtxt/general/site_description';
    public const XML_PATH_GENERATED_CONTENT = 'llmtxt/content/generated_content';
    public const XML_PATH_USE_MANUAL = 'llmtxt/content/use_manual_content';
    public const XML_PATH_MANUAL_CONTENT = 'llmtxt/content/manual_content';
    public const XML_PATH_OPENAI_API_KEY = 'llmtxt/ai_generation/openai_api_key';
    public const XML_PATH_OPENAI_MODEL = 'llmtxt/ai_generation/openai_model';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getCacheLifetime(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_CACHE_LIFETIME,
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

    public function getGeneratedContent(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_GENERATED_CONTENT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function shouldUseManualContent(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_MANUAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getManualContent(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_MANUAL_CONTENT,
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
        ) ?: 'gpt-4o-mini';
    }
}
