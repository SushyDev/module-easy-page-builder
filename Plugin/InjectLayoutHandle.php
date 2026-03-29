<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Plugin;

use Magento\Framework\View\Model\Layout\Merge;
use SushyDev\EasyPageBuilder\Model\ComponentRegistry;

class InjectLayoutHandle
{
    private array $injected = [];

    public function __construct(
        private readonly ComponentRegistry $registry,
    ) {}

    public function afterAddHandle(Merge $subject, Merge $result, array|string $handleName): Merge
    {
        $handles = is_array($handleName) ? $handleName : [$handleName];

        foreach ($handles as $handle) {
            if (isset($this->injected[$handle])) {
                continue;
            }

            if ($this->registry->findByFormName($handle) === null) {
                continue;
            }

            $this->injected[$handle] = true;

            $subject->addUpdate(
                '<update handle="styles"/>' .
                "<referenceContainer name=\"content\">" .
                "<uiComponent name=\"{$handle}\"/>" .
                "</referenceContainer>"
            );
        }

        return $result;
    }
}
