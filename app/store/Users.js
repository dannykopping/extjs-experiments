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

        // create a custom writer to determine errors!
    },

    constructor: function() {

        var me = this;

        var proxy = me.getProxy();
        proxy.store = me;

        this.callParent(arguments);

    },

    load: function() {
        this.callParent(arguments);
    },

    getUsersLike: function(userDetails, userId) {

        this.getProxy().getUsersLike(userDetails, userId)
                .callback(function(){console.log("success", arguments)},
                            function(){console.log("success", arguments)})
                .execute();
        
    }
});