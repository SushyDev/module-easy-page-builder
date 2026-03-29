<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Model\Renderer;

use Magento\Framework\Escaper;

class HyvaPreviewRenderer
{
    public function __construct(
        private readonly Escaper $escaper,
    ) {}

    public function renderPreviewDocument(string $content, string $cssUrl): string
    {
        $cssUrl = $this->escaper->escapeUrl($cssUrl);

        return <<<DOCHTML
<!doctype html>
<html>
<head>
    <link rel="stylesheet" type="text/css" media="all" href="{$cssUrl}"/>
</head>
<body>
    <div data-preview-scope="simple-pagebuilder">{$content}</div>
</body>
</html>
DOCHTML;
    }

    public function renderPreviewIframe(string $srcdoc, string $iframeId): string
    {
        $srcdoc = $this->escaper->escapeHtmlAttr($srcdoc);
        $iframeId = $this->escaper->escapeHtmlAttr($iframeId);
        $iframeIdJs = $this->escaper->escapeJs($iframeId);

        return <<<IFRAME
<iframe id="{$iframeId}" srcdoc="{$srcdoc}" style="width: 100%; border: 0;"></iframe>
<script>
(() => {
    const iframe = document.getElementById('{$iframeIdJs}');
    iframe.addEventListener('load', () => {
        setTimeout(() => {
            const doc = iframe.contentWindow.document;
            const height = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
            iframe.style.height = height + 'px';
        }, 50);
    });
})()
</script>
IFRAME;
    }
}
