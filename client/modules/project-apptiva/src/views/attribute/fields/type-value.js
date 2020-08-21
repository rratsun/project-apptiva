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

        events: _.extend({
            'click [data-action="addNewValue"]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                this.addNewValue();
            },
            'click [data-action="removeGroup"]': function (e) {
                e.stopPropagation();
                e.preventDefault();
                let index = $(e.currentTarget).data('index');
                this.removeGroup(index);
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

            this.listenTo(this.model, 'change', () => this.hideMultilangLocales());
            this.listenTo(this, 'after:render after:save cancel:save', () => this.hideMultilangLocales());
        },

        hideMultilangLocales() {
            const middle = this.getParentView();
            if (this.model.get('type') === 'enum' && this.model.get('isMultilang') && this.mode === 'edit') {
                this.langFieldNames.forEach(field => {
                    const view = middle.getView(field);
                    if (view) {
                        view.hide();
                        view.setReadOnly();
                        view.reRender();
                    }
                });
            } else {
                const record = middle.getParentView();
                if (record && record.dynamicLogic) {
                    record.dynamicLogic.process();
                }
            }
        },

        modifyDataByType(data) {
            const optionGroups = (this.selectedComplex[this.name] || []).map((item, index) => {
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

            return _.extend({
                optionGroups: optionGroups
            }, Dep.prototype.modifyDataByType.call(this, data));
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

            if (this.model.get('type') === 'enum' && this.model.get('isMultilang') && mode === 'edit') {
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

        removeGroup(index) {
            let value = this.selectedComplex[this.name] || [];
            value.splice(index, 1);
            let data = {
                [this.name]: value
            };
            this.langFieldNames.forEach(name => {
                let value = this.selectedComplex[name] || [];
                value.splice(index, 1);
                data[name] = value;
            });
            this.selectedComplex = data;
            this.reRender();
            this.trigger('change');
        },

        modifyFetchByType(data) {
            Dep.prototype.modifyFetchByType.call(this, data);

            if (this.model.get('type') === 'enum' && this.model.get('isMultilang')) {
                this.fetchFromDom();
                Object.entries(this.selectedComplex).forEach(([key, value]) => data[key] = value);
            }

            return data;
        },

        fetchFromDom() {
            if (this.model.get('type') === 'enum' && this.model.get('isMultilang')) {
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

    })
);