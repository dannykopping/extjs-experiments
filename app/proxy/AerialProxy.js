Ext.define('AM.proxy.AerialProxy', {
    extend: 'Ext.data.proxy.Ajax',
    alias : 'proxy.aerial',

    appendId: true,

    service: null,
    method: null,
    parameters: null,

    requestURL: 'http://localhost/play/helloext/aerial/server/server.php/json',

    batchActions: false,

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

                    request.params.data = Ext.AerialJSON.encode(data);
                }

                request.url = this.requestURL + "/" + this.service + "/" + this.method;
                break;
        }

        return this.callParent(arguments);
    }
});