<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class PageBuilder
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $template,
        public readonly string $menuSection = 'content',
        public readonly string $icon = 'icon-pagebuilder-block',
        public readonly int $sortOrder = 100,
    ) {}
}
