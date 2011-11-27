Ext.application({
    name: 'AM',
    requires: ["aerialext.services.UserService"],

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
                    layout: {
                        type: 'vbox',
                        align : 'stretch',
                        pack  : 'start'
                    },
                    items: [
                        {xtype: "userlist", id: "userlist1"},
                        {xtype: "userlist", id: "userlist2"}
                    ]
                }
            ]
        });

        var service = Ext.create("Aerial.UserService");
        service.getUsersLike({firstName:"Dane", lastName:"Ings"}, 200)
                .callback(this.success, this.failure)
                .execute();
    },

    success: function(response)
    {
        var list = Ext.getCmp("userlist1");

        var store = Ext.getStore("Users");
        list.bindStore(store);

        store.loadData(response);

        list.fireEvent("datachanged", this);
    },

    failure: function(response)
    {
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