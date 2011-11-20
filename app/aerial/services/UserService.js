Ext.define('AM.aerial.services.UserService', {
    requires: ['Ext.util.MixedCollection', 'Ext.Ajax'],
    extend: 'Ext.data.proxy.Server',
    alias : 'proxy.userService',

    appendId: true,

    service: null,
    method: null,
    parameters: null,

    requestURL: "http://localhost/play/helloext/aerial/server/server.php/json/UserService",

    params: undefined,

    batchActions: false,

    successCallback: null,
    failureCallback: null,

    store: undefined,
    
    actionMethods: {
        create : 'POST',
        read   : 'GET',
        update : 'POST',
        destroy: 'POST'
    },

    constructor: function() {

        this.callParent(arguments);

    },

    buildUrl: function(request)
    {
        switch (request.action)
        {
            case "read":

                request.params = {};

                if (this.parameters !== undefined)
                {
                    if (Ext.typeOf(this.parameters) === 'array')
                    {
                        var data = [];
                        for (var x = 0; x < this.parameters.length; x++)
                            data.push(this.parameters[x]);
                    }

                    request.params = Ext.AerialJSON.encode(this.params);
                }

                request.url = this.requestURL + "/" + this.method;
                break;
        }

        return this.callParent(arguments);
    },

    getUsersLike: function(userDetails, userId) {

        var me = this;
        me.method = "getUsersLike";

        me.params = {};
        me.params = [userDetails, userId];

        console.log("AE", me.params);

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

        return me;
    },

    read: function(operation, response, store) {

        operation.params = this.params;

        console.log("READ!", operation.params);

        this.callParent(arguments);

    },


    doRequest: function(operation, callback, scope) {
        var writer  = Ext.create("AM.aerial.writer.AerialWriter"),
            request = this.buildRequest(operation, callback, scope);

        if (operation.allowWrite()) {
            request = writer.write(request);
        }

        Ext.apply(request, {
            headers       : this.headers,
            timeout       : this.timeout,
            scope         : this,
            callback      : this.createRequestCallback(request, operation, callback, scope),
            method        : "POST",
            disableCaching: false // explicitly set it to false, ServerProxy handles caching
        });

        Ext.Ajax.request(request);
        console.log(request);

        return request;
    },

    getMethod: function(request) {
        return this.actionMethods[request.action];
    },

    createRequestCallback: function(request, operation, callback, scope) {
        var me = this;

        return function(options, success, response) {
            me.processResponse(success, operation, request, response, callback, scope);
        };
    }
});