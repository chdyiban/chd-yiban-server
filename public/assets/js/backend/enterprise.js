define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'enterprise/index',
                    add_url: 'enterprise/add',
                    edit_url: 'enterprise/edit',
                    del_url: 'enterprise/del',
                    multi_url: 'enterprise/multi',
                    table: 'enterprise',
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
                        {field: 'Qymc', title: __('Qymc')},
                        {field: 'Fzr', title: __('Fzr')},
                        {field: 'Lxfs', title: __('Lxfs')},
                        {field: 'Dz', title: __('Dz')},
                        {field: 'Qyjj', title: __('Qyjj')},
                        {field: 'Qyxq', title: __('Qyxq')},
                        {field: 'Qygm', title: __('Qygm'), visible:false, searchList: {"0":__('Qygm 0'),"1":__('Qygm 1'),"2":__('Qygm 2'),"3":__('Qygm 3')}},
                        {field: 'Qygm_text', title: __('Qygm'), operate:false},
                        {field: 'Zczj', title: __('Zczj'), operate:'BETWEEN'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"0":__('Status 0'),"1":__('Status 1')}},
                        {field: 'status_text', title: __('Status'), operate:false},
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