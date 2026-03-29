define([
    'underscore',
    'mage/utils/objects',
    'Magento_PageBuilder/js/form/provider',
    'Magento_Rule/conditions-data-normalizer'
], function (_, objectUtils, Provider, ConditionsDataNormalizer) {
    'use strict';

    const serializer = new ConditionsDataNormalizer();
    const combineElement = 'Magento\\CatalogWidget\\Model\\Rule\\Condition\\Combine';

    function processConditions(data, attribute) {
        const pairs = {};

        _.each(data, function (element, key) {
            if (key.indexOf(`parameters[${attribute}]`) === 0) {
                delete data[key];
                pairs[key] = element;
            }
        });

        _.each(pairs, function (element, key) {
            const keyIds    = key.match(/[\d?-]+/g);
            const firstKey  = `parameters[${attribute}][NEXT_ITEM--1][type]`;
            const secondKey = `parameters[${attribute}][NEXT_ITEM--2][type]`;

            if (keyIds !== null && element === combineElement) {
                if (pairs[firstKey.replace('NEXT_ITEM', keyIds[0])] === undefined ||
                    pairs[firstKey.replace('NEXT_ITEM', keyIds[0])] === combineElement &&
                    pairs[secondKey.replace('NEXT_ITEM', keyIds[0])] === undefined) {
                    pairs[key] = '';
                }
            }
        });

        if (!_.isEmpty(pairs)) {
            objectUtils.nested(data, attribute, JSON.stringify(serializer.normalize(pairs).parameters[attribute]));
        }
    }

    return Provider.extend({
        /** @inheritdoc **/
        save: function () {
            const data = this.get('data');
            const seen = {};

            Object.keys(data).forEach(function (key) {
                const match = key.match(/^parameters\[([^\]]+)\]/);
                if (match && !seen[match[1]]) {
                    seen[match[1]] = true;
                    processConditions(data, match[1]);
                }
            });

            return this._super();
        }
    });
});
