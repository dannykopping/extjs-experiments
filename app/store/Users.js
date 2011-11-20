Ext.define('AM.store.Users', {
    extend: 'Ext.data.Store',
    model: 'AM.model.User',
    storeId: "userStore",

    proxy: {
        type: 'userService',

        api: {
            read: 'http://localhost/play/helloext/aerial/server/server.php',
            update: 'http://localhost/play/helloext/aerial/server/server.php'
        },
        reader: {
            type: 'json',
            root: 'data',
            successProperty: 'success'
        }
    },

    load: function() {
        this.callParent(arguments);
    },

    getUsersLike: function()
    {
        var proxy = this.getProxy();
        proxy.store = this;

        var paramArgs = [];
        for(var x = 1; x < arguments.length; x++)
            paramArgs.push(arguments[x]);

        proxy.params = paramArgs;

        console.log(arguments);
        proxy.getUsersLike(arguments[0], arguments[1])
                .callback(function(){console.log("success", arguments)},
                            function(){console.log("success", arguments)})
                .execute();

        this.load();
    }
});