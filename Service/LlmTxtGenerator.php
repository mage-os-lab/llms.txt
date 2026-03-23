<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Service;

use MageOS\LlmTxt\Client\OpenAi\ResponsesParams;
use MageOS\LlmTxt\Client\OpenAi\ResponsesParamsFactory;
use MageOS\LlmTxt\Client\OpenAi\Client as OpenAiClient;
use MageOS\LlmTxt\Config\Config;
use Psr\Log\LoggerInterface;

class LlmTxtGenerator
{
    public const INSTRUCTIONS = 'You are an expert at creating concise, well-structured llms.txt files that help AI systems understand website content. You follow the llmstxt.org standard precisely.';
    public const MAX_OUTPUT_TOKENS = 2000;
    public const TEMPERATURE = 0.7;

    public function __construct(
        private readonly StoreDataCollector $storeDataCollector,
        private readonly OpenAiClient $openAiClient,
        private readonly Config $config,
        private readonly PromptBuilder $promptBuilder,
        private readonly LoggerInterface $logger,
        private readonly ResponsesParamsFactory $responsesParamsFactory,
    ) {}

    public function generateLlmTxt(int $storeId): string
    {
        $storeData = $this->storeDataCollector->collect($storeId);

        $model = $this->config->getOpenAiModel();
        $prompt = $this->promptBuilder->buildPrompt($storeData);

        if ($this->config->isLogPromptEnabled($storeId)) {
            $this->logger->info('LlmTxt prompt', ['store_id' => $storeId, 'model' => $model, 'prompt' => $prompt]);
        }

        /** @var ResponsesParams $params */
        $params = $this->responsesParamsFactory->create()
            ->setModel($model)
            ->setPrompt($prompt)
            ->setInstructions(self::INSTRUCTIONS)
            ->setMaxOutputTokens(self::MAX_OUTPUT_TOKENS)
            ->setTemperature(self::TEMPERATURE);

        $llmTxt = $this->openAiClient->postResponses($params);

        $additionalContent = $this->config->getAdditionalContent($storeId);
        if (!empty($additionalContent)) {
            $llmTxt .= "\n\n$additionalContent";
        }

        return $llmTxt;
    }

    public function estimateTokenCount(string $content): int
    {
        // Rough estimation: 1 token ≈ 0.75 words
        $wordCount = str_word_count($content);
        return (int) ceil($wordCount * 1.3);
    }
}
