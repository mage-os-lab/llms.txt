<?php

declare(strict_types=1);

namespace MageOS\LlmTxt\Model\OpenAi;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use MageOS\LlmTxt\Model\Config;
use Psr\Log\LoggerInterface;

class Client
{
    private const API_ENDPOINT = 'https://api.openai.com/v1/responses';
    private const TIMEOUT = 60;

    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly LoggerInterface $logger,
        private readonly Config $config
    ) {
    }

    public function generateLlmsTxt(array $storeData, ?int $storeId = null): string
    {
        $apiKey = $this->config->getOpenAiApiKey($storeId);
        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured');
        }

        $model = $this->config->getOpenAiModel($storeId);
        $prompt = $this->buildPrompt($storeData);

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

    private function buildPrompt(array $storeData): string
    {
        $categoriesText = $this->formatCategories($storeData['categories'] ?? []);
        $productsText = $this->formatProducts($storeData['products'] ?? []);
        $pagesText = $this->formatPages($storeData['cms_pages'] ?? []);

        return <<<PROMPT
Create an llms.txt file for this Magento eCommerce store.

The llms.txt format helps AI systems understand website content. Follow this structure:

# Store Name
> Brief compelling description (1-2 sentences)

Optional additional context paragraph

## Section Name
- [Link Title](URL): Brief description (1 sentence)

REQUIREMENTS:
1. Start with store name as H1
2. Include engaging blockquote description
3. Organize content into 2-4 logical H2 sections (e.g., "Categories", "Featured Products", "Customer Resources")
4. Use clear, concise language
5. Keep TOTAL output under 1500 words / 2000 tokens
6. Only include the most important/representative items
7. Make descriptions compelling but brief

STORE DATA:
Store Name: {$storeData['store_name']}
Store URL: {$storeData['store_url']}

Top Categories:
{$categoriesText}

Sample Products:
{$productsText}

Key Pages:
{$pagesText}

Generate ONLY the llms.txt content. No explanations or preamble.
PROMPT;
    }

    private function formatCategories(array $categories): string
    {
        $lines = [];
        foreach ($categories as $category) {
            $name = $category['name'];
            $description = mb_substr($category['description'] ?? '', 0, 100);
            $url = $category['url'];

            $lines[] = "- $name ($url): $description";
        }

        return implode("\n", $lines) ?: 'No categories available';
    }

    private function formatProducts(array $products): string
    {
        $lines = [];
        $count = 0;
        foreach ($products as $product) {
            if ($count++ >= 10) {
                break;
            }

            $name = $product['name'];
            $description = mb_substr($product['description'] ?? '', 0, 80);
            $url = $product['url'];

            $lines[] = "- $name ($url): $description";
        }

        return implode("\n", $lines) ?: 'No products available';
    }

    private function formatPages(array $pages): string
    {
        $lines = [];
        foreach ($pages as $page) {
            $title = $page['title'];
            $identifier = $page['identifier'];
            $url = $page['url'];

            $lines[] = "- $title ($url): $identifier";
        }

        return implode("\n", $lines) ?: 'No pages available';
    }
}
