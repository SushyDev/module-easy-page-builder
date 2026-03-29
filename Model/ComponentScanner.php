<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Model;

use ReflectionClass;
use ReflectionProperty;
use SushyDev\EasyPageBuilder\Field\FieldTypeInterface;
use SushyDev\EasyPageBuilder\Attribute\PageBuilder as PageBuilderAttribute;

class ComponentScanner
{
    private const PREVIEW_COMPONENT = 'SushyDev_EasyPageBuilder/js/content-type/preview';
    private const PREVIEW_TEMPLATE  = 'SushyDev_EasyPageBuilder/content-type/simple/preview';
    private const MASTER_TEMPLATE   = 'SushyDev_EasyPageBuilder/content-type/simple/master';
    private const BASE_COMPONENT    = 'Magento_PageBuilder/js/content-type';
    private const MASTER_COMPONENT  = 'Magento_PageBuilder/js/content-type/master';
    private const WIDGET_CONVERTER  = 'SushyDev_EasyPageBuilder/js/mass-converter/widget-directive';

    /** @param string[] $classes FQCN list */
    public function scan(array $classes): array
    {
        $contentTypes = [];

        foreach ($classes as $class) {
            $config = $this->scanClass($class);
            if ($config !== null) {
                $contentTypes[$config['name']] = $config;
            }
        }

        return $contentTypes;
    }

    private function scanClass(string $class): ?array
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(PageBuilderAttribute::class);

        if (empty($attributes)) {
            return null;
        }

        /** @var PageBuilderAttribute $pb */
        $pb     = $attributes[0]->newInstance();
        $fields = $this->scanFields($reflection);

        return [
            'name'              => $pb->name,
            'label'             => $pb->label,
            'icon'              => $pb->icon,
            'form'              => 'pagebuilder_' . $pb->name . '_form',
            'menu_section'      => $pb->menuSection,
            'component'         => self::BASE_COMPONENT,
            'preview_component' => self::PREVIEW_COMPONENT,
            'master_component'  => self::MASTER_COMPONENT,
            'is_system'         => true,
            'allowed_parents'   => ['root-container', 'row', 'column', 'tab-item'],
            'fields'            => [],
            'breakpoints'       => [],
            'additional_data'   => $this->buildAdditionalData($pb->template, $class, $fields),
            'appearances'       => [
                'default' => [
                    'default'          => 'true',
                    'preview_template' => self::PREVIEW_TEMPLATE,
                    'master_template'  => self::MASTER_TEMPLATE,
                    'reader'           => 'Magento_PageBuilder/js/master-format/read/configurable',
                    'elements'         => $this->buildElements($fields),
                    'converters'       => [
                        [
                            'name'      => 'widget_directive',
                            'component' => self::WIDGET_CONVERTER,
                            'config'    => [
                                'html_variable'     => 'html',
                                'content_type_name' => $pb->name,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @param array<string, FieldTypeInterface> $fields */
    private function buildAdditionalData(string $template, string $class, array $fields): array
    {
        $plain   = array_keys(array_filter($fields, fn($f) => !$f->requiresEncoding()));
        $encoded = array_keys(array_filter($fields, fn($f) => $f->requiresEncoding()));

        $data = [
            'template'    => $this->strArrayItem($template),
            'block_class' => $this->strArrayItem($class),
        ];

        if ($plain) {
            $data['fields'] = $this->strArrayItem(implode(',', $plain));
        }

        if ($encoded) {
            $data['encoded_fields'] = $this->strArrayItem(implode(',', $encoded));
        }

        return $data;
    }

    /** @param array<string, FieldTypeInterface> $fields */
    private function buildElements(array $fields): array
    {
        $mainAttributes = [
            [
                'var'               => 'name',
                'name'              => 'data-content-type',
                'converter'         => '',
                'preview_converter' => '',
                'persistence_mode'  => 'readwrite',
                'reader'            => 'Magento_PageBuilder/js/property/attribute-reader',
            ],
            [
                'var'               => 'appearance',
                'name'              => 'data-appearance',
                'converter'         => '',
                'preview_converter' => '',
                'persistence_mode'  => 'readwrite',
                'reader'            => 'Magento_PageBuilder/js/property/attribute-reader',
            ],
        ];

        foreach ($fields as $propertyName => $field) {
            $mainAttributes[] = [
                'var'               => $propertyName,
                'name'              => 'data-' . str_replace('_', '-', $propertyName),
                'converter'         => '',
                'preview_converter' => '',
                'persistence_mode'  => 'readwrite',
                'reader'            => 'Magento_PageBuilder/js/property/attribute-reader',
            ];
        }

        return [
            'main' => [
                'style'      => [
                    [
                        'var'               => 'display',
                        'name'              => 'display',
                        'converter'         => 'Magento_PageBuilder/js/converter/style/display',
                        'preview_converter' => 'Magento_PageBuilder/js/converter/style/preview/display',
                        'persistence_mode'  => 'readwrite',
                        'reader'            => 'Magento_PageBuilder/js/property/style-property-reader',
                    ],
                    [
                        'var'               => 'margins_and_padding',
                        'name'              => 'margin',
                        'converter'         => 'Magento_PageBuilder/js/converter/style/margins',
                        'preview_converter' => '',
                        'persistence_mode'  => 'readwrite',
                        'reader'            => 'Magento_PageBuilder/js/property/margins',
                    ],
                    [
                        'var'               => 'margins_and_padding',
                        'name'              => 'padding',
                        'converter'         => 'Magento_PageBuilder/js/converter/style/paddings',
                        'preview_converter' => '',
                        'persistence_mode'  => 'readwrite',
                        'reader'            => 'Magento_PageBuilder/js/property/paddings',
                    ],
                ],
                'attributes' => $mainAttributes,
                'html'       => [
                    'var'               => 'html',
                    'converter'         => '',
                    'preview_converter' => 'Magento_PageBuilder/js/converter/attribute/preview/store-id',
                ],
                'css'        => ['var' => 'css_classes', 'filter' => []],
                'tag'        => [],
            ],
        ];
    }

    /** @return array<string, FieldTypeInterface> */
    private function scanFields(ReflectionClass $reflection): array
    {
        $fields = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            foreach ($property->getAttributes() as $attr) {
                $instance = $attr->newInstance();
                if ($instance instanceof FieldTypeInterface) {
                    $fields[$property->getName()] = $instance;
                    break;
                }
            }
        }

        uasort(
            $fields,
            static fn(FieldTypeInterface $a, FieldTypeInterface $b) => $a->getSortOrder() <=> $b->getSortOrder(),
        );

        return $fields;
    }

    /** @return array<string, mixed> */
    private function strArrayItem(string $value): array
    {
        return [
            'xsi:type' => 'array',
            'item'     => [
                'value' => ['xsi:type' => 'string', 'value' => $value],
            ],
        ];
    }
}
