<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Field;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ConditionsField implements FieldTypeInterface
{
    public function __construct(
        private readonly string $label,
        private readonly int $sortOrder = 10,
    ) {}

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function requiresEncoding(): bool
    {
        return true;
    }

    public function getEncodingType(): string
    {
        return FieldTypeInterface::ENCODING_WIDGET;
    }

    public function buildXml(string $propertyName, int $sortOrder, string $formNamespace = ''): string
    {
        $label = htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8');

        return <<<XML
        <htmlContent name="$propertyName" sortOrder="$sortOrder" template="Magento_PageBuilder/form/element/widget-conditions" component="Magento_PageBuilder/js/form/element/html">
            <settings>
                <visible>true</visible>
                <additionalClasses>
                    <class name="admin__field">true</class>
                </additionalClasses>
            </settings>
            <block name="$propertyName" class="Magento\PageBuilder\Block\Adminhtml\Form\Element\ProductConditions">
                <arguments>
                    <argument name="formNamespace" xsi:type="string">$formNamespace</argument>
                    <argument name="attribute" xsi:type="string">$propertyName</argument>
                    <argument name="label" xsi:type="string" translate="true">$label</argument>
                </arguments>
            </block>
        </htmlContent>
        XML;
    }
}
