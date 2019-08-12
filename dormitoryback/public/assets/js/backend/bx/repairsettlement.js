define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bx/repairsettlement/index',
                    add_url: 'bx/repairsettlement/add',
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
                        {field: 'id', title: __('ID'),visible: false},
                        {field: 'list_id', title: __('订单id'),visible: false},
                        // {field: 'getcompanyname.nickname', title: __('维修单位'),formatter:function(value){
                        //     if (value == "总控") {
                        //         return "自修";                              
                        //     } else {
                        //         return value;
                        //     }
                        // }},
                        {field: 'repair_company_id', title: __('维修单位'),formatter:function(value){
                            if (value == "1") {
                                return "动力";
                            } else if(value = "2") {
                                return "修建";
                            }
                        }},
                        {field: 'repair_address', title: __('维修地点')},
                        {field: 'getrepairname.name', title: __('维修内容')},
                        {field: 'receipt_number', title: __('收据号码')},
                        {field: 'money', title: __('花费（元）')},
                        {field: 'submit_person', title: __('报送人')},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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