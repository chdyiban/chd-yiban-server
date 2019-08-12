define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'company/companyinfo/index',
                    add_url: 'company/companyinfo/add',
                    edit_url: 'company/companyinfo/edit',
                    del_url: 'company/companyinfo/del',
                    multi_url: 'company/companyinfo/multi',
                    table: 'company_info',
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
                        {field: 'category.name', title: __('Category_id')},
                        {field: 'categorys.name', title: __('Category_ids')},
                        {field: 'connect_person', title: __('Connect_person')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'zone', title: __('Zone')},
                        {field: 'summary', title: __('Summary')},
                        {field: 'getname.nickname', title: __('Admin_id')},
                        {field: 'type', title: __('Type'), visible:false, searchList: {"shanghui":__('type shanghui'),"xiehui":__('type xiehui'),"xuehui":__('type xuehui'),"qiye":__('type qiye')}},
                        {field: 'type_text', title: __('Type'), operate:false},
                        {field: 'number', title: __('Number')},
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