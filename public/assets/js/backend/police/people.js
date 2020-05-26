define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'police/people/index',
                    add_url: 'police/people/add',
                    edit_url: 'police/people/edit',
                    del_url: 'police/people/del',
                    multi_url: 'police/people/multi',
                    table: 'police_people',
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
                        {field: 'getcategoryname.name', title: __('区域名称'),searchList: $.getJSON("police/people/getCategory"),},
                        {field: 'name', title: __('Name')},
                        {field: 'sex', title: __('Sex'), visible:false, searchList: {"0":__('Sex 0'),"1":__('Sex 1')}},
                        {field: 'sex_text', title: __('Sex'), operate:false},
                        {field: 'idcard', title: __('Idcard')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'image', title: __('Image'), operate:false,formatter: Table.api.formatter.image},
                        {field: 'type', title: __('Type'), visible:false, searchList: {"0":__('Type 0'),"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3'),"4":__('Type 4')}},
                        {field: 'type_text', title: __('Type'), operate:false},
                        {field: 'police_station', title: '警务室', visible:false, searchList: {"1":"双槐中心警务室","2":"太和中心警务室","3":"白马杨中心警务室","4":"白王村中心警务室"}},
                        {field: 'police_station_text', title: '警务室', operate: false},
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