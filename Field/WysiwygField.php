<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Field;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class WysiwygField implements FieldTypeInterface
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
        return FieldTypeInterface::ENCODING_HTML_ENTITIES;
    }

    public function buildXml(string $propertyName, int $sortOrder, string $formNamespace = ''): string
    {
        $label = htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8');

        return <<<XML
        <field name="$propertyName" formElement="wysiwyg" sortOrder="$sortOrder">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="source" xsi:type="string">page</item>
                    <item name="wysiwygConfigData" xsi:type="array">
                        <item name="is_pagebuilder_enabled" xsi:type="boolean">false</item>
                        <item name="toggle_button" xsi:type="boolean">false</item>
                    </item>
                </item>
            </argument>
            <settings>
                <label translate="true">$label</label>
                <dataType>text</dataType>
                <dataScope>$propertyName</dataScope>
            </settings>
            <formElements>
                <wysiwyg>
                    <settings>
                        <wysiwyg>true</wysiwyg>
                    </settings>
                </wysiwyg>
            </formElements>
        </field>
        XML;
    }
}
