define([
    'Magento_PageBuilder/js/mass-converter/widget-directive-abstract',
    'Magento_PageBuilder/js/config',
    'Magento_PageBuilder/js/utils/object'
], function (BaseWidgetDirective, Config, objectUtils) {
    'use strict';

    function WidgetDirective() {
        BaseWidgetDirective.apply(this, arguments);
    }

    WidgetDirective.prototype = Object.create(BaseWidgetDirective.prototype);
    WidgetDirective.prototype.constructor = WidgetDirective;

    /**
     * @param {string} content
     * @returns {string}
     */
    WidgetDirective.prototype.encodeWysiwygCharacters = function (content) {
        return content
            .replace(/\{/g, '^[').replace(/\}/g, '^]')
            .replace(/"/g, '`').replace(/\\/g, '|')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    };

    /**
     * @param {string} content
     * @returns {string}
     */
    WidgetDirective.prototype.decodeWysiwygCharacters = function (content) {
        return content
            .replace(/\^\[/g, '{').replace(/\^\]/g, '}')
            .replace(/`/g, '"').replace(/\|/g, '\\')
            .replace(/&lt;/g, '<').replace(/&gt;/g, '>');
    };

    /**
     * @param {Object} additionalData
     * @returns {string[]}
     */
    WidgetDirective.prototype.getFieldList = function (additionalData, group) {
        const entry = additionalData[group];
        if (!entry || !entry.value) {
            return [];
        }
        return entry.value.split(',').map(function (f) { return f.trim(); }).filter(Boolean);
    };

    /**
     * @param {Object} data
     * @param {Object} config
     * @returns {Object}
     */
    WidgetDirective.prototype.fromDom = function (data, config) {
        const attributes      = BaseWidgetDirective.prototype.fromDom.call(this, data, config);
        const contentTypeName = config.content_type_name;
        const additionalData  = Config.getContentTypeConfig(contentTypeName).additional_data || {};

        this.getFieldList(additionalData, 'fields').forEach(function (key) {
            if (attributes[key] !== undefined) {
                data[key] = attributes[key];
            }
        });

        this.getFieldList(additionalData, 'encoded_fields').forEach(function (key) {
            if (attributes[key] !== undefined) {
                data[key] = this.decodeWysiwygCharacters(attributes[key]);
            }
        }.bind(this));

        return data;
    };

    /**
     * @param {Object} data
     * @param {Object} config
     * @returns {Object}
     */
    WidgetDirective.prototype.toDom = function (data, config) {
        const contentTypeName = config.content_type_name;
        const additionalData  = Config.getContentTypeConfig(contentTypeName).additional_data || {};
        const blockClass      = additionalData.block_class && additionalData.block_class.value;
        const template        = additionalData.template && additionalData.template.value;

        const attrs = { type: blockClass, template: template };

        this.getFieldList(additionalData, 'fields').forEach(function (key) {
            if (data[key] !== undefined && data[key] !== '') {
                attrs[key] = data[key];
            }
        });

        this.getFieldList(additionalData, 'encoded_fields').forEach(function (key) {
            if (data[key] !== undefined && data[key] !== '') {
                attrs[key] = this.encodeWysiwygCharacters(String(data[key]));
            }
        }.bind(this));

        objectUtils.set(data, config.html_variable, '{{widget ' + this.createAttributesString(attrs) + '}}');

        return data;
    };

    return WidgetDirective;
});
