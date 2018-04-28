define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company/businessinfo/index',
                    add_url: 'company/businessinfo/add',
                    edit_url: 'company/businessinfo/edit',
                    del_url: 'company/businessinfo/del',
                    multi_url: 'company/businessinfo/multi',
                    table: 'business_info',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                        {field: 'company', title: __('Company')},
                        {field: 'content', title: __('Content')},
                        {field: 'money', title: __('Money')},
                        {field: 'cooperation', title: __('Cooperation')},
                        {field: 'benefit', title: __('Benefit')},
                        {field: 'connect_person', title: __('Connect_person')},
                        {field: 'connect_mobile', title: __('Connect_mobile')},
                        {field: 'connect_company', title: __('Connect_company')},
                        {field: 'connect_email', title: __('Connect_email')},
                        {field: 'message_origin', title: __('Message_origin')},
                        {field: 'origin_adress', title: __('Origin_adress')},
                        {field: 'type', title: __('Type'), visible:false, searchList: {"nongye":__('type nongye'),"gongye":__('type gongye'),"lvyou":__('type lvyou'),"fangchan":__('type fangchan')}},
                        {field: 'type_text', title: __('Type'), operate:false},
                        {field: 'admin_id', title: __('Admin_id')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});