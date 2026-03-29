<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Model\Renderer;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\View\Element\Template;
use Magento\PageBuilder\Model\Stage\RendererInterface;

class Phtml implements RendererInterface
{
    public function __construct(
        private readonly BlockFactory $blockFactory,
        private readonly ResultFactory $resultFactory,
        private readonly ModuleManager $moduleManager,
        private readonly HyvaPreviewRenderer $hyvaRenderer,
        private readonly string $prefix = 'simple-pagebuilder-',
    ) {}

    /** @param array<string, mixed> $params */
    public function render(array $params): array
    {
        $template = $params['template'] ?? null;
        $blockClass = $params['block_class'] ?? Template::class;

        if (!$template) {
            return ['content' => null, 'error' => 'No template specified.'];
        }

        $block = $this->blockFactory->createBlock($blockClass, ['data' => $params]);
        $block->setTemplate($template);

        $pageResult = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $pageResult->initLayout();
        $pageResult->getLayout()->addBlock($block);

        $html = $block->toHtml();

        if ($this->moduleManager->isEnabled('Hyva_Theme')) {
            $html = $this->wrapWithHyvaStyles($html, $block);
        }

        return ['content' => $html];
    }

    private function wrapWithHyvaStyles(string $html, Template $block): string
    {
        $cssUrl = $block->getViewFileUrl('css/styles.css');
        $iframeId = sprintf('%s%s', $this->prefix, uniqid());

        $previewDocument = $this->hyvaRenderer->renderPreviewDocument($html, $cssUrl);
        return $this->hyvaRenderer->renderPreviewIframe($previewDocument, $iframeId);
    }
}
