Ext.define('AM.model.User', {
    extend: 'Ext.data.Model',
    fields: ['id', 'firstName', 'lastName', 'email'],
    
    set: function() {
        this.callParent(arguments);
    }
});