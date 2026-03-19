<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Model\OpenAi;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use MageOS\LlmTxt\Model\Config;
use MageOS\LlmTxt\Model\PromptBuilder;
use Psr\Log\LoggerInterface;

class Client
{
    public const API_ENDPOINT = 'https://api.openai.com/v1/responses';
    public const TIMEOUT = 60;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger,
        private readonly Config $config,
        private readonly PromptBuilder $promptBuilder,
    ) {
    }

    public function generateLlmsTxt(array $storeData, ?int $storeId = null): string
    {
        $apiKey = $this->config->getOpenAiApiKey($storeId);
        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }

        $model = $this->config->getOpenAiModel($storeId);
        $prompt = $this->promptBuilder->buildPrompt($storeData);

        try {
            $response = $this->httpClient->post(self::API_ENDPOINT, [
                'timeout' => self::TIMEOUT,
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'instructions' => 'You are an expert at creating concise, well-structured llms.txt files that help AI systems understand website content. You follow the llmstxt.org standard precisely.',
                    'input' => $prompt,
                    'max_output_tokens' => 2000,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            if (!empty($body['output_text']) && is_string($body['output_text'])) {
                return trim($body['output_text']);
            }

            if (!empty($body['output']) && is_array($body['output'])) {
                $text = $this->extractTextFromResponsesOutput($body['output']);
                if ($text !== '') {
                    return trim($text);
                }
            }

            throw new \RuntimeException('Invalid response from OpenAI API');

        } catch (GuzzleException $e) {
            $this->logger->error('OpenAI API request failed', [
                'exception' => $e->getMessage(),
                'store_id' => $storeId,
            ]);

            $statusCode = method_exists($e, 'getResponse') && $e->getResponse()
                ? $e->getResponse()->getStatusCode()
                : (int) $e->getCode();

            if ($statusCode === 401) {
                throw new \RuntimeException('Invalid OpenAI API key. Please check your credentials.');
            }

            if ($statusCode === 429) {
                throw new \RuntimeException('OpenAI rate limit reached. Please try again in a few moments.');
            }

            throw new \RuntimeException('Failed to generate content: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI API request failed', [
                'exception' => $e->getMessage(),
                'store_id' => $storeId,
            ]);

            if ((int) $e->getCode() === 401) {
                throw new \RuntimeException('Invalid OpenAI API key. Please check your credentials.');
            }

            if ((int) $e->getCode() === 429) {
                throw new \RuntimeException('OpenAI rate limit reached. Please try again in a few moments.');
            }

            throw new \RuntimeException('Failed to generate content: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Extract plain text from Responses API output items.
     */
    private function extractTextFromResponsesOutput(array $output): string
    {
        $parts = [];

        foreach ($output as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            if (empty($item['content']) || !is_array($item['content'])) {
                continue;
            }

            foreach ($item['content'] as $contentItem) {
                if (($contentItem['type'] ?? null) === 'output_text' && isset($contentItem['text'])) {
                    $parts[] = $contentItem['text'];
                }
            }
        }

        return trim(implode("\n", $parts));
    }
}
