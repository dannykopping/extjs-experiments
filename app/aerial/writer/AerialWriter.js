/*

This file is part of Ext JS 4

Copyright (c) 2011 Sencha Inc

Contact:  http://www.sencha.com/contact

GNU General Public License Usage
This file may be used under the terms of the GNU General Public License version 3.0 as published by the Free Software Foundation and appearing in the file LICENSE included in the packaging of this file.  Please review the following information to ensure the GNU General Public License version 3.0 requirements will be met: http://www.gnu.org/copyleft/gpl.html.

If you are unsure which license is appropriate for your use, please contact the sales department at http://www.sencha.com/contact.

*/
/**
 * @class Ext.data.writer.Json
 * @extends Ext.data.writer.Writer

This class is used to write {@link Ext.data.Model} data to the server in a JSON format.
The {@link #allowSingle} configuration can be set to false to force the records to always be
encoded in an array, even if there is only a single record being sent.

 * @markdown
 */
Ext.define('AM.aerial.writer.AerialWriter', {
    extend: 'Ext.data.writer.Json',
    alias: 'writer.aerial',

    writeRecords: function(request, data) {
        var root = this.root;
        
        if (this.allowSingle && data.length == 1) {
            // convert to single object format
            data = data[0];
        }
        
        if (this.encode) {
            if (root) {
                // sending as a param, need to encode
                request.params[root] = Ext.AerialJSON.encode(data);
            } else {
                //<debug>
                Ext.Error.raise('Must specify a root when using encode');
                //</debug>
            }
        } else {
            // send as jsonData
            request.jsonData = request.jsonData || {};
            if (root) {
                request.jsonData[root] = data;
            } else {
                request.jsonData = data;
            }
        }
        return request;
    }
});

