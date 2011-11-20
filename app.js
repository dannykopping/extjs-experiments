Ext.application({
    name: 'AM',
    requires: ["AM.proxy.AerialProxy", "AM.aerial.services.UserService"],

    appFolder: 'app',

    controllers: [
        "Users"
    ],

    launch: function()
    {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: [
                {
                    layout: "fit",
                    items: {
                        xtype: "userlist"
                    }
                }
            ]
        });

        var service = new Ext.create("UserService");
        service.getUsersLike({firstName:"Danny", lastName:"Kopping"}, 200)
                .callback(this.success, this.failure)
                .execute();
    },

    success: function(response) {
        console.log("getUsersLike Response: ", response);

        var store = Ext.getStore("Users");
        store.loadData(response);
    },

    failure: function(response) {
        console.log("getUsersLike Failure: ", response);
    }

//        var view = Ext.widget("userlist");
//        console.log(view.getStore());
//
//        var store = Ext.getStore("AM.aerial.services.UserStore");
//        store.getUsersLike(function(data) {
//            view.getStore().loadData(data)
//        }, {firstName:"Danny", lastName:"Kopping"}, 200);
//
//        var newFilter = {id:1, name:"Bob", active:undefined, userId:undefined};
//        store.saveAerialObj(null, newFilter);
});