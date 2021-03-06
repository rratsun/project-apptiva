Espo.define('project-apptiva:views/product/modals/mass-update-attributes', 'views/modals/mass-update',
    Dep => Dep.extend({
        template: 'project-apptiva:product/modals/mass-update-attributes',

        notEditType: ['enum', 'multiEnum'],

        insertMode: 0,

        deleteMode: 0,

        data: function () {
            return {
                scope: this.scope,
                attributes: this.attributes
            };
        },

        events: {
            'click button[data-action="update"]': function () {
                this.actionUpdate();
            },
            'change input[data-action="insert-mode"]': function () {
                this.actionInsertMode();
            },
            'change input[data-action="delete-mode"]': function () {
                this.actionDeleteMode();
            },
            'click a[data-action="add-attribute"]': function (e) {
                let attributeId = $(e.currentTarget).data('attribute-id');
                this.actionRenderAttribute(attributeId, '');

                let deleteMode = this.deleteMode;
                setTimeout(function () {
                    if (deleteMode === 1) {
                        $('.fields-container .field .multi').each(function () {
                            $(this).parent().parent().addClass('delete_mode_multi');
                        });
                    }
                }, 100);
            },
            'click button[data-action="reset"]': function (e) {
                $('a[data-action="removeAttribute"]').click();
            },
            'click a[data-action="removeAttribute"]': function (e) {
                let attributeId = $(e.currentTarget).data('attribute-id');
                this.actionRemoveAttribute(attributeId);
            }
        },

        setup: function () {

            this.createButtonList();
            this.copyOptionsToThis();
            this.initAttributes();

            this.renderedAtrributes = [];
        },

        /**
         * Add attribute to fields container
         * @param attributeId
         */
        actionRenderAttribute: function (attributeId, lang) {
            this.enableButton('update');

            this.currentLang = lang;

            this.$el.find('[data-action="reset"]').removeClass('hidden');
            this.$el.find('ul.filter-list li[data-attribute-id="' + attributeId + '"]').addClass('hidden');

            if (this.$el.find('ul.filter-list li:not(.hidden)').size() == 0) {
                this.$el.find('button.select-field').addClass('disabled').attr('disabled', 'disabled');
            }

            this.notify('Loading...');

            let attribute = this.attributes[attributeId];

            this.renderBodyAttribute(attribute, lang);

            this.updateModelDefs(attribute);

            this.createValueFieldView(attribute);
        },

        /**
         * Render html body attribute
         * @param attribute
         */
        renderBodyAttribute(attribute) {
            let label = attribute.name;
            let id = attribute.attributeId;
            let labelValue = (this.currentLang) ? 'Value › ' + this.currentLang : 'Value';

            let html =
                '<div class="cell form-group col col-sm-6" data-attribute-id="' + id + this.currentLang + '">'
                + '<label class="control-label">' + labelValue + '</label>'
                + '<div class="field" data-attribute-id="' + id + this.currentLang + '" />'
                + '</div>';

            //if main local
            if (this.currentLang === '') {
                let btnRemove = '<a data-attribute-id="' + id + '" href="javascript:" class="cell pull-right" data-action="removeAttribute"><span class="fas fa-times"></a>';
                this.$el
                    .find('.fields-container')
                    .append(
                        '<label data-attribute-id="' + id + '" class="control-label">' + label + '</label>'
                        + btnRemove
                        + '<div class="cell row" data-attribute-id="' + id + '"><hr style="margin-bottom: 25px;margin-top: 0px;">' + html + '</div>');
            } else if (this.currentLang !== '' && this.notEditType.includes(attribute.attributeType)) {
            } else {
                this.$el.find('.row[data-attribute-id="' + attribute.attributeId + '"]').append(html);
            }
        },

        /**
         * @param attribute
         */
        createValueFieldView(attribute) {
            const name = `${attribute.attributeId}${this.currentLang}`;

            this.model.set({[`value${name}`]: this.getDefaultValue(attribute)});
            this.model.set({value: null});

            this.createView(name, this.getFieldManager().getViewName(attribute.attributeType), {
                el: `${this.options.el} .field[data-attribute-id="${name}"]`,
                model: this.model,
                name: `value${name}`,
                labelText: attribute.name,
                mode: this.getMode(attribute.attributeType),
                inlineEditDisabled: this.getInlineEditDisabled(attribute.attributeType)
            }, view => {
                if (this.currentLang === '') {
                    this.renderedAtrributes.push(attribute.attributeId);
                };
                if (attribute.attributeIsMultilang && this.currentLang == '') {
                    let langs = this.getInputLanguage();
                    langs.forEach(lang => {
                        this.actionRenderAttribute(attribute.attributeId, lang);
                    });
                }
                view.render();
                view.notify(false);
            });

        },

        /**
         * @param attribute
         */
        getDefaultValue(attribute) {
            let value = null;
            if (['enum'].includes(attribute.attributeType) && attribute.typeValue.length > 0) {
                value = attribute.typeValue.slice(0, 1)[0]
            } else if (attribute.attributeType === 'bool') {
                value = false;
            }

            return value;
        },

        /**
         * @param attribute
         */
        updateModelDefs(attribute) {
            if (attribute.attributeType) {
                let fieldDefs = {
                    type: attribute.type,
                    options: attribute.typeValue,
                    measure: (attribute.typeValue || ['Length'])[0],
                    view: attribute.attributeType !== 'bool' ? this.getFieldManager().getViewName(attribute.attributeType) : 'pim:views/fields/bool-required',
                    required: true
                };

                this.model.defs.fields['value' + attribute.attributeId + this.currentLang] = fieldDefs;
            }
        },

        /**
         * @param attributeId
         */
        actionRemoveAttribute(attributeId) {
            this.clearView(attributeId);
            this.$el.find('.cell[data-attribute-id="' + attributeId + '"]').remove();
            this.$el.find('label[data-attribute-id="' + attributeId + '"]').remove();

            delete this.model.defs.fields['value' + attributeId];

            if (this.attributes[attributeId].attributeIsMultilang) {
                let langs = this.getInputLanguage();
                langs.forEach((lang) => {
                    this.clearView(attributeId + lang);
                    delete this.model.defs.fields['value' + attributeId + lang];
                });
            }
            for (let i = 0; i < this.renderedAtrributes.length; i++) {
                if (this.renderedAtrributes[i] === attributeId) {
                    delete this.renderedAtrributes[i];
                    break;
                }
            }
            this.currentLang = '';

            this.$el.find('button.select-field').removeClass('disabled').removeAttr('disabled');
            this.$el.find('ul.filter-list').find('li[data-attribute-id="' + attributeId + '"]').removeClass('hidden');
        },

        actionInsertMode: function () {
            this.insertMode = this.$el.find('input[data-action="insert-mode"]:checked').length;

            this.$el.find('input[data-action="delete-mode"]:checked').click();
        },

        actionDeleteMode: function () {
            this.deleteMode = this.$el.find('input[data-action="delete-mode"]:checked').length;

            if (this.deleteMode === 1) {
                this.$el.find('.fields-container .field .multi').each(function () {
                    $(this).parent().parent().addClass('delete_mode_multi');
                });
                this.$el.find('.fields-container').addClass('delete_mode');
            } else {
                this.$el.find('.fields-container').removeClass('delete_mode');
                this.$el.find('.fields-container .cell .cell').removeClass('delete_mode_multi');
            }

            this.$el.find('input[data-action="insert-mode"]:checked').click();
        },

        /**
         * Action Update
         */
        actionUpdate: function () {
            this.disableButton('update');
            if (this.isValidAttributes()) {
                let count = 0;
                this.renderedAtrributes.forEach(attributeId => {
                    this.notify('Saving...');
                    let whereUpdate = this.getWhereUpdate(attributeId);
                    let attributes = {};
                    attributes.value = this.getValue(attributeId);
                    if (this.attributes[attributeId].attributeIsMultilang) {
                        let langs = this.getInputLanguage();
                        langs.forEach((lang) => {
                            attributes['value' + lang] = this.getValue(attributeId, lang)
                        });
                    }

                    let value = this.getView(attributeId).model.get('value' + attributeId + 'Unit');
                    if (typeof value !== 'undefined') {
                        attributes.data = {"unit": value};
                    }

                    if (this.insertMode) {
                        attributes.massUpdateMode = 'insert';
                    }

                    if (this.deleteMode) {
                        attributes.massUpdateMode = 'delete';
                    }

                    $.ajax({
                        url: 'ProductAttributeValue' + '/action/massUpdate',
                        type: 'PUT',
                        async: false,
                        data: JSON.stringify({
                            attributes: attributes,
                            where: whereUpdate,
                            select: ['attributeId', 'attributeName', 'productId', 'productName'],
                            byWhere: true
                        }),
                        success: function (result) {
                            count += (result || {}).count;
                        }.bind(this),
                        error: function () {
                            this.notify('Error occurred', 'error');
                            this.enableButton('update');
                        }.bind(this)
                    });
                });

                this.trigger('after:update', count);
            }
        },

        /**
         * @returns {array}
         */
        getWhereUpdate(attributeId) {
            // get selected products ids
            let productsIds  = this.ids;

            // prepare where
            let whereUpdate = [];
            whereUpdate.push({attribute: 'attributeId', type: "equals", value: attributeId});
            whereUpdate.push({attribute: 'productId', type: "in", value: productsIds});
            whereUpdate.push({attribute: 'scope', type: "equals", value: 'Global'});

            return whereUpdate;
        },

        /**
         * @param attributeId
         * @param lang
         * @returns {string}
         */
        getValue(attributeId, lang = '') {
            let view = this.getView(attributeId + lang);
            let value = view.model.get('value' + attributeId + lang);

            if ((this.attributes[attributeId].attributeType || '') === 'image') {
                value = view.model.get('value' + attributeId + lang + 'Id');
            } else if (Array.isArray(value)) {
                value = JSON.stringify(value);
            }

            return value
        },

        /**
         * Check isValidAttributes
         * @returns {boolean}
         */
        isValidAttributes(lang) {
            if (this.deleteMode){
                return true
            }

            let notValid = false;
            let attributes = {};
            this.renderedAtrributes.forEach(attributeId => {
                let view = this.getView(attributeId);
                _.extend(attributes, view.fetch());
                if (this.attributes[attributeId].attributeIsMultilang) {
                    let langs = this.getInputLanguage();
                    langs.forEach(lang => {
                        let view = this.getView(attributeId);
                        _.extend(attributes, view.fetch());
                    });
                }
            });

            this.model.set(attributes);
            this.renderedAtrributes.forEach(attributeId => {
                let view = this.getView(attributeId);
                notValid = view.validate() || notValid;
                if (this.attributes[attributeId].attributeIsMultilang) {
                    let langs = this.getInputLanguage();
                    langs.forEach(lang => {
                        let view = this.getView(attributeId);
                        notValid = view.validate() || notValid;
                    });
                }
            });

            if (notValid) {
                this.notify('Not valid', 'error');
                this.enableButton('update');
            }
            return !notValid;
        },

        /**
         * Init attribute
         */
        initAttributes() {
            this.wait(true);

            this.getModelFactory().create('ProductAttributeValue', function (model) {
                let productsIds = this.ids;
                this.ajaxPostRequest('ApptivaProduct/action/getAttributesForMassUpdate', {productsIds: productsIds}).then(response => {
                    this.attributes = response.attributes || [];
                    this.wait(false);
                });

                this.model = model;
            }.bind(this));
        },

        /**
         * Create buttons List
         */
        createButtonList() {
            this.buttonList = [
                {
                    name: 'update',
                    label: 'Update',
                    style: 'danger',
                    disabled: true
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];
        },

        /**
         *
         * @returns {array}
         */
        getInputLanguage() {
            let langs = this.getConfig().get('inputLanguageList') || [];
            return langs.map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
        },

        /**
         * Add options to this
         */
        copyOptionsToThis() {
            this.scope = this.options.scope;
            this.ids = this.options.ids;
            this.where = this.options.where;
            this.selectData = this.options.selectData;
            this.byWhere = this.options.byWhere;

            this.header = this.translate(this.scope, 'scopeNamesPlural') + ' &raquo ' + this.translate('Mass Update Attributes', 'labels', 'Product');
        },

        /**
         * @param type
         */
        getMode(type) {
            return this.notEditType.includes(type) && this.currentLang !== '' ? 'detail' : 'edit'
        },

        /**
         * @param type
         */
        getInlineEditDisabled(type) {
            return this.notEditType.includes(type) && this.currentLang !== '';
        }
    })
);