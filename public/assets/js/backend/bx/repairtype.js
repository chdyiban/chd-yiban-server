define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bx/repairtype/index',
                    add_url: 'bx/repairtype/add',
                    edit_url: 'bx/repairtype/edit',
                    del_url: 'bx/repairtype/del',
                    multi_url: 'bx/repairtype/multi',
                    table: 'repair_type',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'specific_id',
                sortName: 'specific_id',
                columns: [
                    [
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name')},
                      //  {checkbox: false},
                        {field: 'specific_id', title: __('Specific_id')},
                        {field: 'specific_name', title: __('Specific_name')},
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