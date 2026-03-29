<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Field;

interface FieldTypeInterface
{
    public const ENCODING_NONE = 'none';
    public const ENCODING_HTML_ENTITIES = 'html_entities';
    public const ENCODING_WIDGET = 'widget';

    public function getLabel(): string;

    public function getSortOrder(): int;

    public function requiresEncoding(): bool;

    public function getEncodingType(): string;

    public function buildXml(string $propertyName, int $sortOrder, string $formNamespace = ''): string;
}
