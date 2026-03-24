<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Service;

use MageOS\LlmTxt\Data\SectionItem;
use MageOS\LlmTxt\Data\StoreContext;

class PromptBuilder
{
    public const MAX_DESCRIPTION_LENGTH = 255;

    public function buildPrompt(StoreContext $storeData): string
    {
        $sectionCount = $this->countSections($storeData);
        $descriptionLine = $storeData->getDescription() ? 'Store Description: ' . $storeData->getDescription() : null;

        $categorySection = $this->formatSection('Top Categories', $storeData->getCategories() ?: []);
        $productSection = $this->formatSection('Sample Products', $storeData->getProducts() ?: []);
        $pageSection = $this->formatSection('Key Pages', $storeData->getCmsPages() ?: []);

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
3. Organize content into $sectionCount logical H2 section(s) (e.g., "Categories", "Featured Products", "Customer Resources")
4. Use clear, concise language
5. Keep TOTAL output under 1500 words / 2000 tokens
6. Only include the most important/representative items
7. Make descriptions compelling but brief
8. Remove repetitive marketing phrases and SEO-like noise
9. Write all text only in the language of the store locale

STORE DATA:
Store Name: {$storeData->getName()}
Store URL: {$storeData->getUrl()}
Store Locale: {$storeData->getLocale()}
$descriptionLine

$categorySection

$productSection

$pageSection

Generate ONLY the llms.txt content. No explanations or preamble.
PROMPT;
    }

    /**
     * @param string $sectionName
     * @param SectionItem[] $sectionItems
     * @return string
     */
    private function formatSection(string $sectionName, array $sectionItems): string
    {
        if (!$sectionItems) {
            return '';
        }

        $lines = [];
        foreach ($sectionItems as $sectionItem) {
            $name = (string) $sectionItem->getName();
            $description = mb_substr((string) $sectionItem->getDescription(), 0, self::MAX_DESCRIPTION_LENGTH);
            $url = (string )$sectionItem->getUrl();

            $lines[] = "- [$name]($url): $description";
        }

        return $sectionName . ":\n" . implode("\n", $lines);
    }

    private function countSections(StoreContext $storeContext): int
    {
        $sectionCandidates = [
            $storeContext->getProducts(),
            $storeContext->getCategories(),
            $storeContext->getCmsPages(),
        ];

        return count(array_filter($sectionCandidates));
    }
}
