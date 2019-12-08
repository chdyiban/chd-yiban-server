define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'form/questionnairelist/index',
                    add_url: 'form/questionnairelist/add',
                    edit_url: 'form/questionnairelist/edit',
                    del_url: 'form/questionnairelist/del',
                    multi_url: 'form/questionnairelist/multi',
                    table: 'form_questionnaire',
                }
            });

            var table = $("#table");
            var formId = $("#formId").val();
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url+"?form="+formId,
                pk: 'ID',
                sortName: 'ID',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'ID', title: __('Id')},
                        {field: 'form_id', title: __('Form_id')},
                        {field: 'title', title: __('Title')},
                        {field: 'type', title: __('Type'), visible:false, searchList: {"text":__('text'),"textarea":__('textarea'),"selector":__('selector'),"radio":__('radio'),"image":__('image'),"position":__('position'),"checkbox":__('checkbox')}},
                        {field: 'type_text', title: __('Type'), operate:false},
                        {field: 'extra', title: __('Extra'), visible:false, searchList: {"selector":__('selector'),"multiSelector":__('multiSelector'),"time":__('time'),"date":__('date'),"region":__('region')}},
                        {field: 'extra_text', title: __('Extra'), operate:false},
                        {field: 'placeholder', title: __('Placeholder')},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"0":__('不可见'),"1":__('可见')}},
                        {field: 'status_text', title: __('Status'), operate:false},
                        {field: 'must', title: __('Must'), visible:false, searchList: {"0":__('非必填'),"1":__('必填')}},
                        {field: 'must_text', title: __('Must'), operate:false},
                        {field: 'validate', title: __('Validate')},
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