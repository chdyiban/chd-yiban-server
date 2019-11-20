define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'conversationrecord/content/index',
                    add_url: 'conversationrecord/content/add',
                    edit_url: 'conversationrecord/content/edit',
                    del_url: 'conversationrecord/content/del',
                    multi_url: 'conversationrecord/content/multi',
                    table: 'record_content',
                }
            });

            var table = $("#table");

            table.on('load-success.bs.table', function (e,value,data) {
                valueList = {}
                $.each(value.rows,function (i,v) {
                    valueList[i] = v["THNR"];
                    // $(this).find("td:eq(2)").html(1);
                })
                $("#table tbody tr").each(function(i,v){
                    console.log(valueList[i]);
                    $(this).find("td:eq(1)").html(valueList[i]);
                    $(this).find("td:eq(1)").attr("style","text-align:left");
                });
            });

            var XSID = $("#ID").val();
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'JLID',
                sortName: 'JLID',
                showToggle: false,
                showColumns: false,
                commonSearch: false,
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        // {field: 'JLID', title: __('ID'),width:20,visible:false,},
                        {field: 'THNR', title: __('谈话内容'),operate:false},
                        {field: 'THSJ', title: __('谈话时间'),formatter:function(value,row){ 
                            if (value == 0) {
                                return "无";
                            } else{
                                var now = new Date(value*1000);
                                var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
                                return time; 
                            }
                        }},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ],
                queryParams: function (params) { //自定义搜索条件
                    var filter = params.filter ? JSON.parse(params.filter) : {}; //判断当前是否还有其他高级搜索栏的条件
                    var op = params.op ? JSON.parse(params.op) : {};  //并将搜索过滤器 转为对象方便我们追加条件
                    filter.XSID = XSID;     //将透传的参数 filter.XSID，追加到搜索条件中
                    op.XH= "=";  //group_id的操作方法的为 找到相等的
                    params.filter = JSON.stringify(filter); //将搜索过滤器和操作方法 都转为JSON字符串
                    params.op = JSON.stringify(op);
                    params.sort = "THSJ";
                    params.order = "desc";
                    //如果希望忽略搜索栏搜索条件,可使用
                    //params.filter = JSON.stringify({url: 'login'});
                    //params.op = JSON.stringify({url: 'like'});
                    return params;    
                },
            });

            $("#addContent").on('click',function () {
                var ID = $('#ID').val();
                var url = $.fn.bootstrapTable.defaults.extend.add_url+"?ID="+ID;
                Fast.api.open(url);

            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            
        },
        add: function () {
            //时间选择模块
            var now = new Date();
            var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
            $('#selectInsertTime').daterangepicker({
                "singleDatePicker": true,
                "startDate": time,
            });

            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
            var now = new Date($('#selectInsertTime').val()*1000);
            var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();

            $('#selectInsertTime').daterangepicker({
                "singleDatePicker": true,
                "startDate": time,
            }, );

        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});