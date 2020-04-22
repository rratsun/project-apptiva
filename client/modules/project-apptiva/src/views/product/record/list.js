Espo.define('project-apptiva:views/product/record/list', 'pim:views/product/record/list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.massActionList = ["remove", "massUpdate", "massAttributeUpdate", "export", "follow", "unfollow", "addRelation", "removeRelation"];
        },
    })
);