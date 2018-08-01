define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitory/dormitorybuilding/index',
                    add_url: 'dormitory/dormitorybuilding/add',
                    edit_url: 'dormitory/dormitorybuilding/edit',
                    del_url: 'dormitory/dormitorybuilding/del',
                    multi_url: 'dormitory/dormitorybuilding/multi',
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
                        // {checkbox: true},
                        {field: 'building', title: __('楼号')},
                        {field: 'rest_dormitory_num', title: __('剩余宿舍数量')},
                        {field: 'rest_bed_num', title: __('剩余床铺数量')},
                        {field: 'rest_num', title: __('剩余床铺数量')},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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