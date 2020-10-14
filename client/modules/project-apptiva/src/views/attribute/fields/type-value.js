/**
 * ProjectApptiva
 * Premium Plugin
 * Copyright (c) TreoLabs GmbH
 *
 * This Software is the property of TreoLabs GmbH and is protected
 * by copyright law - it is NOT Freeware and can be used only in one project
 * under a proprietary license, which is delivered along with this program.
 * If not, see <http://treopim.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

Espo.define('project-apptiva:views/attribute/fields/type-value', 'pim:views/attribute/fields/type-value',
    Dep => Dep.extend({

        _timeouts: {},

        events: _.extend({
            'click [data-action="addNewValue"]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                this.addNewValue();
            },
            'click [data-action="removeGroup"]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                this.removeGroup($(e.currentTarget));
            },
            'change input[data-name][data-index]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                this.trigger('change');
            }
        }, Dep.prototype.events),

        setup() {
            Dep.prototype.setup.call(this);

            this.langFieldNames = this.getLangFieldNames();

            this.updateSelectedComplex();
            const eventStr = this.langFieldNames.reduce((prev, curr) => `${prev} change:${curr}`, `change:${this.name}`);
            this.listenTo(this.model, eventStr, () => this.updateSelectedComplex());

            this.listenTo(this.model, 'change:isMultilang', () => {
                this.setMode(this.mode);
                this.reRender();
            });

            this.listenTo(this, 'change', () => this.reRender());
        },

        afterRender: function () {
            if (this.mode === 'edit') {
                this.$list = this.$el.find('.list-group');
                var $select = this.$select = this.$el.find('.select');

                if (!this.params.options) {
                    $select.on('keypress', function (e) {
                        if (e.keyCode === 13) {
                            var value = $select.val().toString();
                            if (this.noEmptyString) {
                                if (value === '') {
                                    return;
                                }
                            }
                            this.addValue(value);
                            $select.val('');
                        }
                    }.bind(this));
                }
            }

            if (this.mode === 'search') {
                this.renderSearch();
            }

            let deletedRow = $("input[value=todel]").parents('.list-group-item');
            deletedRow.find('a[data-action=removeGroup]').remove();
            deletedRow.hide();

            let removeGroupButtons = $('a[data-action=removeGroup]');
            if (removeGroupButtons.length === 1) {
                removeGroupButtons.remove();
            }
        },

        modifyDataByType(data) {
            data = Espo.Utils.cloneDeep(data);

            if (this.isEnumsMultilang()) {
                data.optionGroups = (this.selectedComplex[this.name] || []).map((item, index) => {
                    return {
                        options: [
                            {
                                name: this.name,
                                value: item,
                                shortLang: ''
                            },
                            ...this.langFieldNames.map(name => {
                                return {
                                    name: name,
                                    value: (this.selectedComplex[name] || [])[index],
                                    shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase()
                                }
                            })
                        ]
                    }
                });
            }

            return data;
        },

        getLangFieldNames() {
            return (this.getConfig().get('inputLanguageList') || []).map(item => {
                return item.split('_').reduce((prev, curr) => {
                    prev = prev + Espo.Utils.upperCaseFirst(curr.toLowerCase());
                    return prev;
                }, this.name);
            });
        },

        updateSelectedComplex() {
            this.selectedComplex = {
                [this.name]: Espo.Utils.cloneDeep(this.model.get(this.name)) || []
            };
            this.langFieldNames.forEach(name => {
                this.selectedComplex[name] = Espo.Utils.cloneDeep(this.model.get(name)) || []
            });
        },

        setMode(mode) {
            Dep.prototype.setMode.call(this, mode);

            if (this.isEnumsMultilang() && mode !== 'list') {
                this.template = 'project-apptiva:attribute/fields/type-value/enum-multilang/' + mode;
            }
        },

        resetValue() {
            [this.name, ...this.langFieldNames].forEach(name => this.selectedComplex[name] = null);
            this.model.set(this.selectedComplex);
        },

        addNewValue() {
            let data = {
                [this.name]: (this.selectedComplex[this.name] || []).concat([''])
            };
            this.langFieldNames.forEach(name => {
                data[name] = (this.selectedComplex[name] || []).concat([''])
            });
            this.selectedComplex = data;
            this.reRender();
            this.trigger('change');
        },

        removeGroup(el) {
            let index = el.data('index');
            let value = this.selectedComplex[this.name] || [];
            value[index] = 'todel';
            // value.splice(index, 1);
            let data = {
                [this.name]: value
            };
            this.langFieldNames.forEach(name => {
                let value = this.selectedComplex[name] || [];
                value[index] = 'todel';
                // value.splice(index, 1);
                data[name] = value;
            });
            this.selectedComplex = data;
            this.reRender();
            this.trigger('change');
        },

        modifyFetchByType(data) {
            Dep.prototype.modifyFetchByType.call(this, data);

            if (this.isEnumsMultilang()) {
                this.fetchFromDom();
                Object.entries(this.selectedComplex).forEach(([key, value]) => data[key] = value);
            }

            return data;
        },

        fetchFromDom() {
            if (this.isEnumsMultilang()) {
                const data = {};
                data[this.name] = [];
                this.langFieldNames.forEach(name => data[name] = []);
                this.$el.find('.option-group').each((index, element) => {
                    $(element).find('.option-item input').each((i, el) => {
                        const $el = $(el);
                        const name = $el.data('name').toString();
                        data[name][index] = $el.val().toString();
                    });
                });
                this.selectedComplex = data;
            } else {
                Dep.prototype.fetchFromDom.call(this);
            }
        },

        validateRequired() {
            const values = this.model.get(this.name);
            let error = !values || !values.length;
            values.forEach((value, i) => {
                if (!value) {
                    let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate('Value'));
                    this.showValidationMessage(msg, `input[data-name="${this.name}"][data-index="${i}"]`);
                    error = true;
                }
            });

            return error;
        },

        showValidationMessage: function (message, target) {
            var $el;

            target = target || '.array-control-container';

            if (typeof target === 'string' || target instanceof String) {
                $el = this.$el.find(target);
            } else {
                $el = $(target);
            }

            if (!$el.size() && this.$element) {
                $el = this.$element;
            }
            $el.popover({
                placement: 'bottom',
                container: 'body',
                content: message,
                trigger: 'manual',
                html: true
            }).popover('show');

            var isDestroyed = false;

            $el.closest('.field').one('mousedown click', function () {
                if (isDestroyed) return;
                $el.popover('destroy');
                isDestroyed = true;
            });

            this.once('render remove', function () {
                if (isDestroyed) return;
                if ($el) {
                    $el.popover('destroy');
                    isDestroyed = true;
                }
            });

            if (this._timeouts[target]) {
                clearTimeout(this._timeouts[target]);
            }

            this._timeouts[target] = setTimeout(function () {
                if (isDestroyed) return;
                $el.popover('destroy');
                isDestroyed = true;
            }, 3000);
        },

        isEnumsMultilang() {
            return (this.model.get('type') === 'enum' || this.model.get('type') === 'multiEnum') && this.model.get('isMultilang');
        }

    })
);