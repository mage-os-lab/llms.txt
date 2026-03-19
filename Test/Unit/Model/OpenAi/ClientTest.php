<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Test\Unit\Model\OpenAi;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use MageOS\LlmTxt\Model\OpenAi\Client;

final class ClientTest extends TestCase
{
    private Client $client;
    private ScopeConfigInterface|MockObject $scopeConfig;
    private EncryptorInterface|MockObject $encryptor;
    private HttpClient|MockObject $httpClient;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->encryptor = $this->createMock(EncryptorInterface::class);
        $this->httpClient = $this->createMock(HttpClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new Client(
            $this->scopeConfig,
            $this->encryptor,
            $this->httpClient,
            $this->logger
        );
    }

    public function test_generate_llms_txt_throws_exception_when_no_api_key(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key is not configured');

        $this->scopeConfig->method('getValue')->willReturn('');
        $this->encryptor->method('decrypt')->willReturn('');

        $this->client->postResponses([], 1);
    }

    public function test_generate_llms_txt_returns_content_on_success(): void
    {
        $storeData = [
            'store_name' => 'Test Store',
            'store_url' => 'https://example.com/',
            'categories' => [],
            'products' => [],
            'cms_pages' => [],
        ];

        $expectedContent = "# Test Store\n> AI generated content";
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => $expectedContent
                    ]
                ]
            ]
        ];

        // Mock config
        $this->scopeConfig->method('getValue')
            ->willReturnCallback(function ($path) {
                if (str_contains($path, 'api_key')) {
                    return 'encrypted_key';
                }
                return 'gpt-4o-mini';
            });

        $this->encryptor->method('decrypt')->with('encrypted_key')->willReturn('sk-test-key');

        // Mock HTTP response
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($apiResponse));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->expects($this->once())
            ->method('post')
            ->willReturn($response);

        $result = $this->client->postResponses($storeData, 1);

        $this->assertSame($expectedContent, $result);
    }

    public function test_generate_llms_txt_handles_invalid_api_key_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid OpenAI API key');

        $this->scopeConfig->method('getValue')
            ->willReturnCallback(function ($path) {
                if (str_contains($path, 'api_key')) {
                    return 'encrypted_key';
                }
                return 'gpt-4o-mini';
            });

        $this->encryptor->method('decrypt')->willReturn('invalid-key');

        $exception = new class('Unauthorized', 401) extends \Exception {
        };

        $this->httpClient->method('post')->willThrowException($exception);

        $this->client->postResponses(['store_name' => 'Test'], 1);
    }

    public function test_generate_llms_txt_handles_rate_limit_error(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('rate limit');

        $this->scopeConfig->method('getValue')
            ->willReturnCallback(function ($path) {
                if (str_contains($path, 'api_key')) {
                    return 'encrypted_key';
                }
                return 'gpt-4o-mini';
            });

        $this->encryptor->method('decrypt')->willReturn('sk-test-key');

        $exception = new class('Rate limit exceeded', 429) extends \Exception {
        };

        $this->httpClient->method('post')->willThrowException($exception);

        $this->client->postResponses(['store_name' => 'Test'], 1);
    }

    public function test_generate_llms_txt_logs_errors(): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnCallback(function ($path) {
                if (str_contains($path, 'api_key')) {
                    return 'encrypted_key';
                }
                return 'gpt-4o-mini';
            });

        $this->encryptor->method('decrypt')->willReturn('sk-test-key');

        $exception = new class('Server error', 500) extends \Exception {
        };

        $this->httpClient->method('post')->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('OpenAI API request failed', $this->anything());

        try {
            $this->client->postResponses(['store_name' => 'Test'], 1);
        } catch (\RuntimeException $e) {
            // Expected
        }
    }
}
