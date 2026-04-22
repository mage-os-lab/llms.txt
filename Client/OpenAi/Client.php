<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Client\OpenAi;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use MageOS\LlmTxt\Config\Config;
use MageOS\LlmTxt\Client\OpenAi\ResponsesParams;

class Client
{
    public const BASE_URL = 'https://api.openai.com';
    public const TIMEOUT = 60;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly Config $config,
    ) {}

    public function postResponses(ResponsesParams $params, ?string $apiKeyOverride = null): string
    {
        $apiKey = $apiKeyOverride !== null && $apiKeyOverride !== ''
            ? $apiKeyOverride
            : $this->config->getOpenAiApiKey();
        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }

        $requestBody = $this->buildRequestBody($apiKey, $params);

        try {
            $response = $this->httpClient->post(self::BASE_URL . '/v1/responses', $requestBody);
            $responseBody = json_decode((string) $response->getBody(), true);

            return $this->getTextFromResponseBody($responseBody);
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()->getStatusCode();

            if ($statusCode === 401) {
                throw new \RuntimeException('Invalid OpenAI API key. Please check your credentials.');
            }

            if ($statusCode === 429) {
                throw new \RuntimeException('OpenAI rate limit reached. Please try again in a few moments.');
            }

            throw new \RuntimeException('Failed to generate content: ' . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to generate content: ' . $e->getMessage(), 0, $e);
        }
    }

    private function buildRequestBody(string $apiKey, ResponsesParams $params): array
    {
        $body = [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $params->getModel(),
                'instructions' => $params->getInstructions(),
                'input' => $params->getPrompt(),
                'max_output_tokens' => $params->getMaxOutputTokens(),
            ],
        ];

        if ($this->isTemperatureSupported($params->getModel())) {
            $body['json']['temperature'] = $params->getTemperature();
        }

        return $body;
    }

    private function getTextFromResponseBody(array $body): string
    {
        $output = $body['output'] ?? null;
        if (is_array($output) && $output) {
            $parts = [];

            foreach ($output as $outputItem) {
                if (($outputItem['type'] ?? null) !== 'message') {
                    continue;
                }

                $content = $outputItem['content'] ?? null;
                if (!is_array($content) || !$content) {
                    continue;
                }

                foreach ($content as $contentItem) {
                    if (($contentItem['type'] ?? null) === 'output_text' && isset($contentItem['text'])) {
                        $parts[] = $contentItem['text'];
                    }
                }
            }

            return trim(implode("\n", $parts));
        }

        throw new \RuntimeException('Invalid response from OpenAI API');
    }

    private function isTemperatureSupported(string $model): bool
    {
        return str_contains($model, 'gpt-4');
    }
}
