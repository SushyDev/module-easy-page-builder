<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Plugin;

use Magento\PageBuilder\Model\Config\ContentType\AdditionalData\Parser;
use Magento\PageBuilder\Model\Stage\Config;
use Magento\PageBuilder\Model\Stage\Config\UiComponentConfig;
use SushyDev\EasyPageBuilder\Model\ComponentRegistry;
use SushyDev\EasyPageBuilder\Model\ComponentScanner;


class InjectPageBuilderComponents
{
    public function __construct(
        private readonly ComponentScanner $scanner,
        private readonly ComponentRegistry $registry,
        private readonly Parser $additionalDataParser,
        private readonly UiComponentConfig $uiComponentConfig,
    ) {}

    /** @param array<string, mixed> $result */
    public function afterGetConfig(Config $subject, array $result): array
    {
        $scanned = $this->scanner->scan(array_values($this->registry->all()));

        if (empty($scanned)) {
            return $result;
        }

        $result['content_types'] = array_merge(
            $result['content_types'],
            array_map($this->flatten(...), $scanned),
        );

        return $result;
    }

    /** @param array<string, mixed> $contentType */
    private function flatten(array $contentType): array
    {
        $form = $contentType['form'] ?? '';

        return array_merge($contentType, [
            'additional_data' => isset($contentType['additional_data'])
                ? $this->additionalDataParser->toArray($contentType['additional_data'])
                : [],
            'fields' => $form !== ''
                ? ['default' => $this->uiComponentConfig->getFields($form)]
                : [],
        ]);
    }
}
