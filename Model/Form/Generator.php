<?php

declare(strict_types=1);

namespace SushyDev\EasyPageBuilder\Model\Form;

use ReflectionClass;
use SushyDev\EasyPageBuilder\Field\FieldTypeInterface;
use SushyDev\EasyPageBuilder\Model\ComponentRegistry;

class Generator
{
    public function __construct(
        private readonly ComponentRegistry $registry,
    ) {}

    public function canGenerate(string $formName): bool
    {
        return $this->registry->findByFormName($formName) !== null;
    }

    public function generate(string $formName): string
    {
        $dsName    = $formName . '_data_source';
        $class     = $this->registry->findByFormName($formName);
        $fields    = $class !== null ? $this->reflectFields($class) : [];
        $fieldsXml = $this->buildFieldsXml($fields, $formName);

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd"
              name="$formName"
              extends="pagebuilder_base_form">

            <argument name="data" xsi:type="array">
                <item name="js_config" xsi:type="array">
                    <item name="provider" xsi:type="string">$formName.$dsName</item>
                </item>
                <item name="label" xsi:type="string" translate="true">Component</item>
            </argument>

            <settings>
                <namespace>$formName</namespace>
                <deps>
                    <dep>$formName.$dsName</dep>
                </deps>
            </settings>

            <dataSource name="$dsName">
                <argument name="data" xsi:type="array">
                    <item name="js_config" xsi:type="array">
                        <item name="component" xsi:type="string">SushyDev_EasyPageBuilder/js/form/provider</item>
                    </item>
                </argument>
                <dataProvider name="$dsName" class="Magento\PageBuilder\Model\ContentType\DataProvider">
                    <settings>
                        <requestFieldName/>
                        <primaryFieldName/>
                    </settings>
                </dataProvider>
            </dataSource>

            <fieldset name="appearance_fieldset" sortOrder="10" component="Magento_PageBuilder/js/form/element/dependent-fieldset">
                <settings>
                    <label translate="true">Appearance</label>
                    <additionalClasses>
                        <class name="admin__fieldset-visual-select-large">true</class>
                    </additionalClasses>
                    <collapsible>false</collapsible>
                    <opened>true</opened>
                    <imports>
                        <link name="hideFieldset">\${\$.name}.appearance:options</link>
                        <link name="hideLabel">\${\$.name}.appearance:options</link>
                    </imports>
                </settings>
                <field name="appearance" formElement="select" sortOrder="10" component="Magento_PageBuilder/js/form/element/dependent-visual-select">
                    <argument name="data" xsi:type="array">
                        <item name="config" xsi:type="array">
                            <item name="default" xsi:type="string">default</item>
                        </item>
                    </argument>
                    <settings>
                        <additionalClasses>
                            <class name="admin__field-wide">true</class>
                            <class name="admin__field-visual-select-container">true</class>
                        </additionalClasses>
                        <dataType>text</dataType>
                        <validation>
                            <rule name="required-entry" xsi:type="boolean">true</rule>
                        </validation>
                        <elementTmpl>Magento_PageBuilder/form/element/visual-select</elementTmpl>
                    </settings>
                    <formElements>
                        <select>
                            <settings>
                                <options class="SushyDev\EasyPageBuilder\Source\AppearanceSourceDefault" />
                            </settings>
                        </select>
                    </formElements>
                </field>
            </fieldset>

            <fieldset name="general" sortOrder="20">
                <settings>
                    <label translate="true">Content</label>
                    <collapsible>true</collapsible>
                    <opened>true</opened>
                </settings>
                $fieldsXml
            </fieldset>

        </form>
        XML;
    }

    /** @param array<string, FieldTypeInterface> $fields */
    private function buildFieldsXml(array $fields, string $formNamespace): string
    {
        $sortOrder = 10;
        $parts     = [];

        foreach ($fields as $propertyName => $field) {
            $parts[] = $field->buildXml($propertyName, $sortOrder, $formNamespace);
            $sortOrder += 10;
        }

        return implode("\n", $parts);
    }

    /** @return array<string, FieldTypeInterface> */
    private function reflectFields(string $class): array
    {
        $fields = [];

        foreach ((new ReflectionClass($class))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attr) {
                $instance = $attr->newInstance();
                if ($instance instanceof FieldTypeInterface) {
                    $fields[$property->getName()] = $instance;
                    break;
                }
            }
        }

        uasort(
            $fields,
            static fn(FieldTypeInterface $a, FieldTypeInterface $b) => $a->getSortOrder() <=> $b->getSortOrder(),
        );

        return $fields;
    }
}
