Ext.define('AM.proxy.AerialProxy', {
    extend: 'Ext.data.proxy.Ajax',
    alias : 'proxy.aerial',

    appendId: true,

    service: null,
    method: null,
    parameters: null,

    requestURL: 'http://localhost/play/helloext/aerial/server/server.php/json',

    batchActions: false,

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

                    request.params.data = Ext.JSON.encode(data);
                }

                request.url = this.requestURL + "/" + this.service + "/" + this.method;
                break;
        }

        return this.callParent(arguments);
    },

    callback: function(data)
    {
        console.log("From proxy: " + data);
    },

    encodeParameters: function(params)
    {
        var parameters = {};

        if (params)
        {
            if (Ext.typeOf(this.parameters) === 'array')
            {
                parameters = [];
                for (var x = 0; x < params.length; x++)
                {
                    parameters.push(this.encodeParameters(params[x]));
                }
            }
            else
                parameters = params;
        }
        else
            parameters = (params === undefined) ? undefined : null;

        return parameters;
    }
});