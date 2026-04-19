<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use MageOS\LlmTxt\Config\Config;
use MageOS\LlmTxt\Service\CsvSerializer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private EncryptorInterface&MockObject $encryptor;
    private CsvSerializer $csvSerializer;
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->csvSerializer = new CsvSerializer();

        $this->config = new Config(
            $this->scopeConfig,
            $this->encryptor,
            $this->csvSerializer,
        );
    }

    public function test_is_enabled_returns_true_when_flag_is_set(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->with(Config::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function test_is_enabled_returns_false_when_flag_is_not_set(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->with(Config::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(false);

        $this->assertFalse($this->config->isEnabled());
    }

    public function test_is_enabled_passes_store_id_to_scope_config(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->with(Config::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, 5)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled(5));
    }

    public function test_get_site_name_returns_configured_value(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_SITE_NAME, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('My Store Name');

        $this->assertSame('My Store Name', $this->config->getSiteName());
    }

    public function test_get_site_name_returns_empty_string_when_not_configured(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_SITE_NAME, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(null);

        $this->assertSame('', $this->config->getSiteName());
    }

    public function test_get_site_description_returns_configured_value(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_SITE_DESCRIPTION, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('A great online store');

        $this->assertSame('A great online store', $this->config->getSiteDescription());
    }

    public function test_get_additional_content_returns_configured_value(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_ADDITIONAL_CONTENT, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('Some extra content');

        $this->assertSame('Some extra content', $this->config->getAdditionalContent());
    }

    public function test_get_generated_content_returns_configured_value(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_GENERATED_CONTENT, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('# Store\n> Description');

        $this->assertSame('# Store\n> Description', $this->config->getGeneratedContent());
    }

    public function test_get_openai_api_key_decrypts_stored_value(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_OPENAI_API_KEY, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('encrypted_key');

        $this->encryptor
            ->method('decrypt')
            ->with('encrypted_key')
            ->willReturn('sk-real-api-key');

        $this->assertSame('sk-real-api-key', $this->config->getOpenAiApiKey());
    }

    public function test_get_openai_api_key_returns_empty_string_when_not_configured(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_OPENAI_API_KEY, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(null);

        $this->assertSame('', $this->config->getOpenAiApiKey());
    }

    public function test_get_openai_api_key_does_not_call_decryptor_when_value_is_empty(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->willReturn('');

        $this->encryptor
            ->expects($this->never())
            ->method('decrypt');

        $this->config->getOpenAiApiKey();
    }

    public function test_get_openai_model_returns_configured_value(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_OPENAI_MODEL, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('gpt-4o');

        $this->assertSame('gpt-4o', $this->config->getOpenAiModel());
    }

    public function test_get_category_ids_returns_array_of_integers(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_CATEGORY_IDS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('10, 20, 30');

        $result = $this->config->getCategoryIds();

        $this->assertSame([10, 20, 30], array_values($result));
    }

    public function test_get_category_ids_returns_empty_array_when_not_configured(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_CATEGORY_IDS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('');

        $this->assertSame([], $this->config->getCategoryIds());
    }

    public function test_get_product_skus_returns_array_of_strings(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_PRODUCT_SKUS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('SKU-1, SKU-2, SKU-3');

        $result = $this->config->getProductSkus();

        $this->assertSame(['SKU-1', 'SKU-2', 'SKU-3'], array_values($result));
    }

    public function test_get_product_skus_returns_empty_array_when_not_configured(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_PRODUCT_SKUS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('');

        $this->assertSame([], $this->config->getProductSkus());
    }

    public function test_is_log_prompt_enabled_returns_true_when_flag_is_set(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->with(Config::XML_PATH_LOG_PROMPT, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isLogPromptEnabled());
    }

    public function test_is_log_prompt_enabled_returns_false_when_flag_is_not_set(): void
    {
        $this->scopeConfig
            ->method('isSetFlag')
            ->with(Config::XML_PATH_LOG_PROMPT, ScopeInterface::SCOPE_STORE, null)
            ->willReturn(false);

        $this->assertFalse($this->config->isLogPromptEnabled());
    }

    public function test_get_cms_page_identifiers_returns_array_of_strings(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_CMS_PAGE_IDENTIFIERS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('about-us, contact, faq');

        $result = $this->config->getCmsPageIdentifiers();

        $this->assertSame(['about-us', 'contact', 'faq'], array_values($result));
    }

    public function test_get_cms_page_identifiers_returns_empty_array_when_not_configured(): void
    {
        $this->scopeConfig
            ->method('getValue')
            ->with(Config::XML_PATH_CMS_PAGE_IDENTIFIERS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('');

        $this->assertSame([], $this->config->getCmsPageIdentifiers());
    }

    public function test_xml_path_constants_have_expected_values(): void
    {
        $this->assertSame('llmtxt/general/enabled', Config::XML_PATH_ENABLED);
        $this->assertSame('llmtxt/ai_generation/site_name', Config::XML_PATH_SITE_NAME);
        $this->assertSame('llmtxt/ai_generation/site_description', Config::XML_PATH_SITE_DESCRIPTION);
        $this->assertSame('llmtxt/openai/openai_api_key', Config::XML_PATH_OPENAI_API_KEY);
        $this->assertSame('llmtxt/openai/openai_model', Config::XML_PATH_OPENAI_MODEL);
    }
}
