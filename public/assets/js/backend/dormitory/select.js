define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitory/dormitory/index',
                    add_url: 'dormitory/dormitory/add',
                    edit_url: 'dormitory/dormitory/edit',
                    del_url: 'dormitory/dormitory/del',
                    multi_url: 'dormitory/dormitory/multi',
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
                        {field: 'YXJC', title: __('院系简称')},
                        {field: 'bed_num', title: __('总床位数')},
                        {field: 'finished_num', title: __('已选床位数量')},
                        {field: 'rest_num', title: __('剩余床位数量')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            $("#cxselect-example .col-xs-12").each(function () {
                $("textarea", this).val($(this).prev().prev().html().replace(/[ ]{2}/g, ''));
            });
            
            //这里需要手动为Form绑定上元素事件
            Form.api.bindevent($("form#cxselectform"));

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