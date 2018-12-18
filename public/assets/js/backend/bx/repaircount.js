define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bx/repaircount/index',
                    get_worker_count_url: 'bx/repaircount/getworkercount',
                }
            });

            var table = $("#table");

            table.on('load-success.bs.table', function (e,value,data) {
                var workerIdList = {};
                $.each(value.rows,function (i,v) {
                    workerIdList[i] = v.id;
                    });

                $.ajax({
                    
                        type:'POST',
                        url:$.fn.bootstrapTable.defaults.extend.get_worker_count_url,
                        data:{
                            key: JSON.stringify(workerIdList),
                        },
                        success:function(data){
                            $("#table tbody tr").each(function(i,v){
                                // data_index = $(this).attr('data-index');
                                // $(this).find("td:eq(6)").html(data[i].situation);
                                // $(this).find("td:eq(7)").html(data[i].fullBedNum + "/" + data[i].allBedNum);
                            });
                        }
                    });
                //这里可以获取从服务端获取的JSON数据
            });
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('ID'),visible: false},
                        {field: 'name', title: __('工人姓名')},
                        {field: 'nickname', title: __('所属单位')},
                        {field: 'mobile', title: __('电话')},
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