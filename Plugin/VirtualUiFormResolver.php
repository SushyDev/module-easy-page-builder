<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Plugin;

use SushyDev\EasyPageBuilder\Model\Form\Generator;

class VirtualUiFormResolver
{
    public function __construct(
        private readonly Generator $formGenerator,
    ) {}

    public function aroundGet(
        $subject,
        callable $proceed,
        $filename,
        $scope
    ): array {
        if ($filename === null) {
            return $proceed($filename, $scope);
        }

        $componentName = preg_replace('/\.xml$/', '', (string) $filename);

        if ($this->formGenerator->canGenerate($componentName)) {
            return [
                "virtual://{$componentName}.xml" => $this->formGenerator->generate($componentName),
            ];
        }

        return $proceed($filename, $scope);
    }
}
