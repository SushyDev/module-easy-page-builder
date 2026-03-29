<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Plugin;

use Magento\Widget\Model\Config\Data;
use SushyDev\EasyPageBuilder\Model\Widget\WidgetRegistrar;

class MergeWidgets
{
    public function __construct(
        private readonly WidgetRegistrar $widgetRegistrar,
    ) {}

    public function afterGet(Data $subject, mixed $result, ?string $path = null): mixed
    {
        $widgets = $this->buildWidgets();

        if ($path === null) {
            return is_array($result)
                ? array_merge($widgets, $result)
                : $result;
        }

        $segments = explode('/', $path);
        $widgetId = $segments[0];

        if ($result !== null || !isset($widgets[$widgetId])) {
            return $result;
        }

        $data = $widgets[$widgetId];
        foreach (array_slice($segments, 1) as $segment) {
            if (!is_array($data) || !isset($data[$segment])) {
                return null;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    /** @return array<string, array<string, mixed>> */
    private function buildWidgets(): array
    {
        $widgets = [];

        foreach ($this->widgetRegistrar->getWidgetConfigs() as $id => $config) {
            $widgets[$id] = [
                '@' => ['type' => $config['class']],
                'name' => $config['label'],
                'description' => $config['description'],
                'is_email_compatible' => $config['is_email_compatible'] ? '1' : '0',
                'parameters' => $this->buildParameters($config['parameters']),
            ];
        }

        return $widgets;
    }

    /** @param array<string, array<string, mixed>> $rawParameters */
    private function buildParameters(array $rawParameters): array
    {
        $result = [];

        foreach ($rawParameters as $paramName => $paramConfig) {
            $result[$paramName] = [
                'type' => $paramConfig['xsi:type'] ?? 'text',
                'label' => $paramConfig['label'] ?? ucwords(str_replace('_', ' ', $paramName)),
                'visible' => ($paramConfig['visible'] ?? true) ? '1' : '0',
                'required' => ($paramConfig['required'] ?? false) ? '1' : '0',
            ];
        }

        return $result;
    }
}
