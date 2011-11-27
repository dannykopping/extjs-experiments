Ext.define('aerialext.services.UserService', {
    extend: 'Ext.data.Connection',
    alias: "Aerial.UserService",

    url: "http://localhost/play/helloext/aerial/server/server.php/json/UserService",
    successCallback: null,
    failureCallback: null,

    // can't use "method" as it's a member of Connection already
    methodToCall: undefined,
    params: undefined,

    getUsersLike: function(userDetails, userId) {

        var me = this;
        me.methodToCall = "getUsersLike";
        me.params = Ext.AerialJSON.encode([userDetails, userId]);

        me.addListener("requestcomplete", me.requestCompleteHandler, this);
        me.addListener("requestexception", me.requestFaultHandler, this);

        return me;
    },

    requestCompleteHandler: function(request, response, options)
    {
        var me = this;

        var reader = new Ext.data.JsonReader({
            model:"AM.model.User"
        });

        try
        {
            var read = reader.read(response);
        }
        catch(e)
        {
            if(me.failureCallback) {
                me.failureCallback.apply(me, [e]);
                return;
            }
        }

        if(read.records.length == 1) {
            var record = read.records[0];

            if(record.raw && record.raw.hasOwnProperty("exception") && record.raw.exception === true) {

                if(me.failureCallback) {
                    me.failureCallback.apply(me, [record.raw]);
                    return;
                }

            }

        }

        if(me.successCallback)
            me.successCallback.apply(me, [read.records]);

    },

    requestFaultHandler: function(request, response, options)
    {
        var me = this;

        if(me.failureCallback)
            me.failureCallback.apply(me, [response]);

    },

    callback: function(successHandler, failureHandler) {

        var me = this;

        me.successCallback = successHandler;
        me.failureCallback = failureHandler;

        return me;
    },

    execute: function() {

        var me = this;

        me.request({url:me.url + "/" + me.methodToCall, jsonData:me.params});
    },

    getApplicationNamespace: function() {

        var paths = Ext.Loader.getConfig()["paths"];
        for(var path in paths)
            if(path !== "Ext")
                return path;

        return null;

    }

});