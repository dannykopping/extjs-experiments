Ext.define('AM.controller.Users', {
    extend: 'Ext.app.Controller',
    stores: ["Users"],
    models: ["User"],

    views: [
        'user.List',
        'user.Edit'
    ],

    init: function() {

        this.control({
            'userlist': {
                itemdblclick: this.editUser,
                itemclick: this.sync
            },

            'useredit button[action=save]': {
                click: this.updateUser
            }
        });

    },

    editUser: function(grid, record) {
        var view = Ext.widget("useredit");

        view.down("form").loadRecord(record);
    },

    updateUser: function(button) {
        var win = button.up('window'),
            form = win.down('form'),
            record = form.getRecord(),
            values = form.getValues();

        console.log(record);

        record.set(values);

        console.log(record);
        console.log(record.data.id, record.getChanges());

        win.close();
    },

    sync: function()
    {
        this.getUsersStore().sync();
    }
});