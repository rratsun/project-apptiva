Espo.define('project-apptiva:views/product/actions/action-mass-update-attributes', 'view',
    Dep => Dep.extend({

        actionMassUpdateAttributes: function () {
            if (!this.getAcl().check(this.options.scope, 'edit') && this.getAcl().check('Attribute', 'edit')) {
                this.notify('Access denied', 'error');
                return false;
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));
            let checkedIds = false;
            if (!this.options.allResultIsChecked) {
                checkedIds = this.options.checkedList;
            }

            this.createView('mass-update-attributes', 'project-apptiva:views/product/modals/mass-update-attributes', {
                scope: this.options.scope,
                ids: checkedIds,
                where: this.options.collection.getWhere(),
                selectData: this.options.collection.data,
                byWhere: this.options.allResultIsChecked
            }, function (view) {
                view.render();
                view.notify(false);
                view.once('after:update', function (count, byQueueManager) {
                    view.close();
                    if (count) {
                        let msg = 'massUpdateResult';
                        if (count == 1) {
                            msg = 'massUpdateResultSingle'
                        }
                        this.notify(this.translate(msg, 'messages').replace('{count}', count), 'success');
                    } else if (byQueueManager) {
                        this.notify(this.translate('byQueueManager', 'messages', 'QueueItem'), 'success');
                        Backbone.trigger('showQueuePanel');
                    } else {
                        this.notify(this.translate('noRecordsUpdated', 'messages'), 'warning');
                    }
                }, this);
            }.bind(this));
        },
    })
);