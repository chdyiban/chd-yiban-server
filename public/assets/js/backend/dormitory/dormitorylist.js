define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitory/dormitorylist/index',
                    add_url: 'dormitory/dormitorylist/add',
                    edit_url: 'dormitory/dormitorylist/edit',
                    del_url: 'dormitory/dormitorylist/del',
                    multi_url: 'dormitory/dormitorylist/multi',
                    table: 'fresh_list',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'YXDM',
                sortName: 'YXDM',
                columns: [
                    [
                        {checkbox: true},                       
                        {field: 'XM', title: __('姓名')},
                        {field: 'XB', title: __('性别')},
                        {field: 'option', title: __('是否选择宿舍')},
                        {field: 'XH', title: __('学号')},
                        {field: 'LH', title: __('楼号'), formatter: Table.api.formatter.search},
                        {field: 'SSH', title: __('宿舍号')},
                        {field: 'CH', title: __('床号')},
                        {field: 'YXJC', title: __('学院')},
                        // {field: 'rest_num', title: __('剩余床铺数量')},
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