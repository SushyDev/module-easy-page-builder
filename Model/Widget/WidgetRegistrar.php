<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Model\Widget;

use SushyDev\EasyPageBuilder\Model\ComponentRegistry;
use SushyDev\EasyPageBuilder\Model\ComponentScanner;

class WidgetRegistrar
{
    public function __construct(
        private readonly ComponentScanner $scanner,
        private readonly ComponentRegistry $registry,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function getWidgetConfigs(): array
    {
        $widgets = [];
        $scanned = $this->scanner->scan(array_values($this->registry->all()));

        foreach ($scanned as $componentName => $componentConfig) {
            $additionalData = $componentConfig['additional_data'] ?? [];
            $blockClass = $additionalData['block_class']['item']['value']['value'] ?? null;
            $template = $additionalData['template']['item']['value']['value'] ?? null;

            if (!$blockClass || !$template) {
                continue;
            }

            $widgets[$componentName] = [
                'id' => $componentName,
                'class' => $blockClass,
                'label' => $componentConfig['label'] ?? $componentName,
                'description' => $componentConfig['label'] ?? $componentName,
                'template' => $template,
                'is_email_compatible' => true,
                'ttl' => 86400,
                'parameters' => $this->buildParameters($additionalData),
            ];
        }

        return $widgets;
    }

    /** @param array<string, mixed> $additionalData */
    private function buildParameters(array $additionalData): array
    {
        $parameters = [];

        $fieldsString = $additionalData['fields']['item']['value']['value'] ?? '';
        $encodedFieldsString = $additionalData['encoded_fields']['item']['value']['value'] ?? '';

        $fields = array_filter(explode(',', $fieldsString));
        $encodedFields = array_filter(explode(',', $encodedFieldsString));
        $allFields = array_merge($fields, $encodedFields);

        foreach ($allFields as $fieldName) {
            $fieldName = trim($fieldName);
            $parameters[$fieldName] = [
                'name' => $fieldName,
                'xsi:type' => 'text',
                'required' => false,
                'visible' => true,
                'label' => ucwords(str_replace('_', ' ', $fieldName)),
            ];
        }

        $parameters['template'] = [
            'name' => 'template',
            'xsi:type' => 'select',
            'required' => true,
            'visible' => false,
            'label' => 'Template',
        ];

        return $parameters;
    }
}
