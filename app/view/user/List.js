Ext.define('AM.view.user.List', {
    extend: 'Ext.grid.Panel',
    alias : 'widget.userlist',
    store : 'Users',

    title : 'All Users',

    initComponent: function() {
        this.columns = [
            {header: 'ID',  dataIndex: 'id',  flex: 0},
            {header: 'First Name',  dataIndex: 'firstName',  flex: 1},
            {header: 'Last Name', dataIndex: 'lastName', flex: 1},
            {header: 'Email', dataIndex: 'email', flex: 1}
        ];

        this.callParent(arguments);
    }
});