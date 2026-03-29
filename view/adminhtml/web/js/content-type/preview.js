define([
    'jquery',
    'knockout',
    'mage/translate',
    'Magento_PageBuilder/js/config',
    'Magento_PageBuilder/js/content-type/preview'
], function ($, ko, $t, pbConfig, Preview) {
    'use strict';

    function SimplePreview(contentType, config, observableUpdater) {
        Preview.call(this, contentType, config, observableUpdater);

        this.loading        = ko.observable(false);
        this.previewHtml    = ko.observable('');
        this.displayPreview = ko.observable(false);
        this.placeholderText = ko.observable(config.label);
    }

    SimplePreview.prototype = Object.create(Preview.prototype);
    SimplePreview.prototype.constructor = SimplePreview;

    SimplePreview.prototype.afterObservablesUpdated = function () {
        Preview.prototype.afterObservablesUpdated.call(this);
        this.fetchPreview();
    };

    SimplePreview.prototype.openEdit = function () {
        const appearance = this.contentType.dataStore.get('appearance');
        if (!appearance) {
            this.contentType.dataStore.set('appearance', 'default');
        }
        return Preview.prototype.openEdit.call(this);
    };

    SimplePreview.prototype.fetchPreview = function () {
        const self = this;

        function extractFieldsFromConfig(config) {
            const additional = config.additional_data || {};
            const fieldsValue = additional.fields && additional.fields.value;
            return fieldsValue
                ? fieldsValue.split(',').map(f => f.trim()).filter(Boolean)
                : [];
        }

        function buildFieldData(fields, storeData) {
            const fieldData = {};
            fields.forEach(key => {
                if (storeData[key] !== undefined) {
                    fieldData[key] = storeData[key];
                }
            });
            return fieldData;
        }

        function buildAjaxData(template, blockClass, fieldData) {
            return $.extend({ role: 'simple_phtml', template: template, block_class: blockClass }, fieldData);
        }

        function handlePreviewSuccess(response) {
            if (response && response.data && response.data.content) {
                self.previewHtml(response.data.content);
                self.displayPreview(true);
            } else if (response && response.data && response.data.error) {
                self.placeholderText(response.data.error);
            }
        }

        function handlePreviewError() {
            self.placeholderText($t('Preview failed. Check your connection and try again.'));
        }

        function handlePreviewComplete() {
            self.loading(false);
        }

        const additional = self.config.additional_data || {};
        const template = additional.template && additional.template.value;
        const blockClass = additional.block_class && additional.block_class.value;
        const fields = extractFieldsFromConfig(self.config);

        if (!template) {
            self.placeholderText($t('No template configured.'));
            return;
        }

        const storeData = self.contentType.dataStore.getState();
        const fieldData = buildFieldData(fields, storeData);
        const ajaxData = buildAjaxData(template, blockClass, fieldData);

        self.loading(true);
        self.displayPreview(false);

        $.ajax(pbConfig.getConfig('preview_url'), {
            method: 'POST',
            data: ajaxData
        }).done(response => {
            handlePreviewSuccess(response);
        }).fail(() => {
            handlePreviewError();
        }).always(() => {
            handlePreviewComplete();
        });
    };

    return SimplePreview;
});
