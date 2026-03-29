# Easy PageBuilder

Declarative PageBuilder widget registration for Magento 2 using PHP attributes. No XML configuration needed.

## Features

- **Attribute-based widget registration** — Decorate your block with `#[PageBuilder(...)]` and optional field attributes
- **Automatic UI form generation** — Field types (`InputField`, `TextAreaField`, `WysiwygField`, `ConditionsField`) automatically generate Magento UI component XML
- **Zero XML boilerplate** — No page builder `xml`'s, `preview.html`, `master.html` required; components are registered in-memory via plugins
- **Hyva Theme support** — Automatic integration with Hyva theme for improved admin previews
- **Type-safe field encoding** — Built-in support for HTML entity and widget-encoded field values with transparent decoding

## Installation

### From GitHub via Composer

Add the repository to your project's `composer.json`:

```json
{
  "repositories": [
    {
      "type": "git",
      "url": "https://github.com/sushydev/module-easy-page-builder.git"
    }
  ]
}
```

Then install:

```bash
composer require sushydev/module-easy-page-builder:@dev
bin/magento module:enable SushyDev_EasyPageBuilder
bin/magento setup:upgrade
bin/magento setup:di:compile
```

## Quick Start

Define a PageBuilder widget:

```php
<?php
declare(strict_types=1);

namespace MyVendor\MyModule\Block\Widget;

use Magento\Framework\View\Element\Template;
use SushyDev\EasyPageBuilder\Attribute\PageBuilder;
use SushyDev\EasyPageBuilder\Field\InputField;
use SushyDev\EasyPageBuilder\Field\WysiwygField;

#[PageBuilder(
    name: 'my_widget',
    label: 'My Widget',
    template: 'MyVendor_MyModule::widget/my-widget.phtml',
    menuSection: 'content',
)]
class MyWidget extends Template
{
    #[InputField(label: 'Title', sortOrder: 10)]
    public string $title = '';

    #[WysiwygField(label: 'Content', sortOrder: 20)]
    public string $content = '';
}
```

Register in `di.xml`:

```xml
<type name="SushyDev\EasyPageBuilder\Model\ComponentRegistry">
    <arguments>
        <argument name="components" xsi:type="array">
            <item name="my_widget" xsi:type="string">MyVendor\MyModule\Block\Widget\MyWidget</item>
        </argument>
    </arguments>
</type>
```

That's it! The widget is now registered in PageBuilder.

## Field Types

- **InputField** — Text input field
- **TextAreaField** — Textarea field
- **WysiwygField** — WYSIWYG editor (HTML entities encoded)
- **ConditionsField** — Product conditions selector (widget-encoded)

## Decoding Widget Data

Use the `DecodesWidgetData` mixin in your block to automatically decode field values:

```php
use SushyDev\EasyPageBuilder\Mixin\DecodesWidgetData;

class MyWidget extends Template
{
    use DecodesWidgetData;

    public function getContent(): string
    {
        // Automatically decodes HTML entities and widget encoding
        return (string) $this->getData('content');
    }
}
```

## Architecture

- **Field/** — Field type interfaces and implementations
- **Model/Form/Generator.php** — Generates UI component XML on-the-fly
- **Model/ComponentRegistry.php** — Maps component names to class names
- **Model/ComponentScanner.php** — Reflects on block classes to extract field metadata
- **Plugin/** — Hooks into Magento systems to inject components and forms
- **Mixin/DecodesWidgetData.php** — Trait for automatic field decoding
- **Attribute/PageBuilder.php** — Block-level attribute decorator

## License

Proprietary

