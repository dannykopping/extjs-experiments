Ext.define('AM.aerial.services.UserService', {
    extend: 'Ext.data.Connection',
    requires: ["AM.proxy.AerialProxy"],
    alias: "UserService",

    url: "http://localhost/play/helloext/aerial/server/server.php/json/UserService",
    successCallback: null,
    failureCallback: null,

    // can't use "method" as it's a member of Connection already
    methodToCall: undefined,
    params: undefined,

    getUsersLike: function(userDetails, userId) {

        var me = this;
        me.methodToCall = "getUsersLike";
        me.params = [userDetails, userId];

        me.addListener("requestcomplete", me.requestCompleteHandler, this);

        return me;
    },

    requestCompleteHandler: function(request, response, options)
    {
        var me = this;

        var reader = new Ext.data.JsonReader({
            model:"AM.model.User"
        });

        var read = reader.read(response);

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

    callback: function(successHandler, failureHandler) {

        var me = this;

        me.successCallback = successHandler;
        me.failureCallback = failureHandler;

        return me;
    },

    execute: function() {

        var me = this;

        me.request({url:me.url + "/" + me.methodToCall, jsonData:me.params});
    }

});

    /*
        var conn = new Ext.data.Connection();
        conn.request({url:"http://localhost/play/helloext/aerial/server/server.php/json/UserService/getUsersLike",
                        jsonData:[{firstName:"Danny", lastName:"Kopping"}, 200]});

        conn.addListener("requestcomplete", function(request, response, options)
            {
                var reader = new Ext.data.JsonReader({
                    model:"AM.model.User"
                });

                var store = Ext.getStore("Users");
                var read = reader.read(response);

                store.loadData(read.records);

                console.log(response.responseText);
                console.log(read.records);
            }, this);*/