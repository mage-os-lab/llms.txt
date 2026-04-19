<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Service;

use MageOS\LlmTxt\Config\Config;
use MageOS\LlmTxt\Service\LlmTxtProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LlmTxtProviderTest extends TestCase
{
    private Config&MockObject $config;
    private LlmTxtProvider $provider;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->provider = new LlmTxtProvider($this->config);
    }

    public function test_get_returns_generated_content(): void
    {
        $this->config
            ->method('getGeneratedContent')
            ->with(1)
            ->willReturn('# Store\n> AI written');

        $this->assertSame('# Store\n> AI written', $this->provider->get(1));
    }

    public function test_get_returns_empty_string_when_generated_content_is_empty(): void
    {
        $this->config
            ->method('getGeneratedContent')
            ->willReturn('');

        $this->assertSame('', $this->provider->get(1));
    }

    public function test_get_passes_store_id_to_config(): void
    {
        $this->config
            ->method('getGeneratedContent')
            ->with(42)
            ->willReturn('content');

        $this->assertSame('content', $this->provider->get(42));
    }
}
