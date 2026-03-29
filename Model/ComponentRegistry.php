<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Model;

class ComponentRegistry
{
    /** @param array<string, string> $components name => FQCN */
    public function __construct(
        private readonly array $components = [],
    ) {}

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->components;
    }

    public function getClass(string $name): ?string
    {
        return $this->components[$name] ?? null;
    }

    public function getFormName(string $name): string
    {
        return 'pagebuilder_' . $name . '_form';
    }

    public function findByFormName(string $formName): ?string
    {
        foreach ($this->components as $name => $class) {
            if ($this->getFormName($name) === $formName) {
                return $class;
            }
        }

        return null;
    }
}
