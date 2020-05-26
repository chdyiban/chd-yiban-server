define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'police/category/index',
                    add_url: 'police/category/add',
                    edit_url: 'police/category/edit',
                    del_url: 'police/category/del',
                    multi_url: 'police/category/multi',
                    table: 'police_category',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                showToggle: false,
                showColumns: false,
                search: false,
                showExport: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'name', title: __('区域名称'),searchList: $.getJSON("police/category/getCategoryJson"),},
                        {field: 'image', title: __('Image'), operate:false,formatter: Table.api.formatter.image},
                        {field: 'longitudes', title: __('Longitudes'), operate:false},
                        {field: 'latitudes', title: __('Latitudes'), operate:false},
                        {field: 'color', title: __('标注颜色'), operate:false},
                        {field: 'count', title: __('人数')},
                        {field: 'weigh', title: __('Weigh'),operate:false},
                        {field: 'createtime', title: __('Createtime'), operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:false, addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"1":__('Status 1'),"2":__('Status 2'),"0":__('Status 0')}},
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