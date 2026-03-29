<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Mixin;

use ReflectionClass;
use SushyDev\EasyPageBuilder\Field\FieldTypeInterface;

trait DecodesWidgetData
{
    /** @var array<string, string>|null */
    private ?array $encodedFieldMap = null;

    public function getData($key = '', $index = false): mixed
    {
        $value = parent::getData($key, $index);

        if ($key === '' || !is_string($key) || !is_string($value)) {
            return $value;
        }

        return match ($this->getEncodingTypeForKey($key)) {
            FieldTypeInterface::ENCODING_HTML_ENTITIES => html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            FieldTypeInterface::ENCODING_WIDGET        => $this->decodeWidgetEncoding($value),
            default                                    => $value,
        };
    }

    private function getEncodingTypeForKey(string $key): string
    {
        if ($this->encodedFieldMap === null) {
            $this->encodedFieldMap = $this->buildEncodedFieldMap();
        }

        return $this->encodedFieldMap[$key] ?? FieldTypeInterface::ENCODING_NONE;
    }

    /** @return array<string, string> */
    private function buildEncodedFieldMap(): array
    {
        $map = [];
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                $instance = $attribute->newInstance();
                if ($instance instanceof FieldTypeInterface && $instance->getEncodingType() !== FieldTypeInterface::ENCODING_NONE) {
                    $map[$property->getName()] = $instance->getEncodingType();
                }
            }
        }

        return $map;
    }

    private function decodeWidgetEncoding(string $value): string
    {
        return str_replace(
            ['^[', '^]', '`', '||', '&lt;', '&gt;'],
            ['{',  '}',  '"', '\\\\', '<',    '>'],
            $value
        );
    }
}
