<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Model;

class PromptBuilder
{
    public function buildPrompt(array $storeData): string
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
- [Link Title] (URL): Brief description (1 sentence)

REQUIREMENTS:
1. Start with store name as H1
2. Include engaging blockquote description
3. Organize content into 2-4 logical H2 sections (e.g., "Categories", "Featured Products", "Customer Resources")
4. Use clear, concise language
5. Keep TOTAL output under 1500 words / 2000 tokens
6. Only include the most important/representative items
7. Make descriptions compelling but brief
8. Write all text only in the language of the store locale

STORE DATA:
Store Name: {$storeData['store_name']}
Store URL: {$storeData['store_url']}
Store Locale: {$storeData['store_locale']}

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
