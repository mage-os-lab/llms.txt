<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Service;

use MageOS\LlmTxt\Config\Config;

class LlmTxtProvider
{
    public function __construct(private readonly Config $config) {}

    public function get(int $storeId): string
    {
        return $this->config->getGeneratedContent($storeId);
    }
}
