Ext.define('AM.store.Users', {
    extend: 'Ext.data.Store',
    model: 'AM.model.User',
    storeId: "userStore",
    requires: ["AM.proxy.AerialProxy"],

    proxy: {
        type: 'aerial',

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

    getUsersLike: function(options)
    {
        var proxy = this.getProxy();

        proxy.service = "UserService";
        proxy.method = "getUsersLike";

        var paramArgs = [];
        for(var x = 1; x < arguments.length; x++)
            paramArgs.push(arguments[x]);

        proxy.parameters = proxy.encodeParameters(paramArgs);

        this.load(options);
    },

    saveAerialObj: function(options)
    {
        var proxy = this.getProxy();

        proxy.service = "FilterService";
        proxy.method = "save";

        var paramArgs = [];
        for(var x = 1; x < arguments.length; x++)
            paramArgs.push(arguments[x]);

        proxy.parameters = proxy.encodeParameters(paramArgs);

        this.load(options);
    }
});