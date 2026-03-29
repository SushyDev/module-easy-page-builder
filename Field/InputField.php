<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Field;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class InputField implements FieldTypeInterface
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
        return false;
    }

    public function getEncodingType(): string
    {
        return FieldTypeInterface::ENCODING_NONE;
    }

    public function buildXml(string $propertyName, int $sortOrder, string $formNamespace = ''): string
    {
        $label = htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8');

        return <<<XML
        <field name="$propertyName" formElement="input" sortOrder="$sortOrder">
            <settings>
                <label translate="true">$label</label>
                <dataType>text</dataType>
                <dataScope>$propertyName</dataScope>
            </settings>
        </field>
        XML;
    }
}
