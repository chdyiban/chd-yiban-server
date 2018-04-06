define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bx/repairlist/index',
                    add_url: 'bx/repairlist/add',
                    edit_url: 'bx/repairlist/edit',
                    del_url: 'bx/repairlist/del',
                    multi_url: 'bx/repairlist/multi',
                    table: 'repair_list',
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
                        {field: 'stu_id', title: __('Stu_id')},
                        {field: 'stu_name', title: __('Stu_name')},
                        {field: 'title', title: __('Title')},
                        {field: 'gettype.name', title: __('Service_id')},
                        {field: 'specfic_id', title: __('Specfic_id')},
                        {field: 'getaddress.name', title: __('Address_id')},
                        {field: 'address', title: __('Address')},
                        {field: 'content', title: __('Content')},
                        {field: 'getname.nickname', title: __('Admin_id')},
                        {field: 'accepted_time', title: __('Accepted_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'dispatched_id', title: __('Dispatched_id')},
                        {field: 'dispatched_time', title: __('Dispatched_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'finished_time', title: __('Finished_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'refused_time', title: __('Refused_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'refused_content', title: __('Refused_content')},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status},
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