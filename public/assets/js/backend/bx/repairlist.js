define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            var param = $('#fuck-params').text();
           // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bx/repairlist/index/status/'+param,
                    add_url: 'bx/repairlist/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: '0',
                    del_url: 'bx/repairlist/del',
                    multi_url: 'bx/repairlist/multi',
                    table: 'repair_list',
                }
            });

            var table = $("#table");

            // 初始化表格
             // 初始化表格
             table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'stu_name', title: __('报修人姓名')},
                        {field: 'stu_id', title: __('报修人学号')},                   
                        {field: 'title', title: __('标题')},
                        {field: 'gettypename.name', title: __('服务类型名称')},
                        {field: 'gettypename.specific_name', title: __('服务项目名称')},
                        {field: 'getaddress.name', title: __('报修区域')},
                        {field: 'address', title: __('报修地点')},
                        {field: 'getname.nickname', title: __('受理人')},
                        {field: 'getcompany.nickname', title: __('分配单位')},
                        {field: 'refused_content', title: __('拒绝原因')},
                        {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                        {field: 'status_text', title: __('Status'), operate:false},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,    
                        
                        buttons: [
                                {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                            ],                    
                    }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            //获取选中项
            $(document).on("click", ".btn-selected", function () {
                Layer.alert(JSON.stringify(table.bootstrapTable('getSelections')));
            });

            // 受理按钮
            $(document).on("click", ".btn-accept", function () {
                //在table外不可以使用添加.btn-change的方法
                //只能自己调用Table.api.multi实现
                //如果操作全部则ids可以置为空
                var ids = Table.api.selectedids(table);
                Table.api.multi("changestatus", ids.join(","), table, this);
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },

        detail: function () {
            var ids = $("#id").val();
           //受理方法
            $(document).on('click', '.btn-accept', function () {
                window.location.href="../../accept/ids/"+ids;
                Fast.api.close();
                alert("受理成功，请尽快分配人员！")
                window.parent.location.reload();  
            });   
            //指派单位
            $(document).on('click', '.btn-distribute', function () {
                window.location.href="../../distribute/ids/"+ids;
            });   
            //重新指派单位
            $(document).on('click', '.btn-redistribute', function () {
                window.location.href="../../redistribute/ids/"+ids;
                Fast.api.close();
                alert('可以重新进行分配');
                window.parent.location.reload();  
            });   
            //分配人员
            $(document).on('click', '.btn-dispatch', function () {
                window.location.href="../../dispatch/ids/"+ids;
            });   
            //完成任务
            $(document).on('click', '.btn-finish', function () {
                window.location.href="../../finish/ids/"+ids;
                Fast.api.close();
                alert("该任务已经完成！")
                window.parent.location.reload();  
            }); 

            //驳回任务
            $(document).on('click', '.btn-refuse', function () {
                window.location.href="../../refuse/ids/"+ids;
            }); 
        },

        refuse: function () {
            $(document).on('click', '.btn-refuse', function () {
                Fast.api.close();
                window.parent.location.reload();  
            });          
        },

        distribute: function () {
            $(document).on('click', '.btn-distribute', function () {
                Fast.api.close();
                alert('分配成功');
                window.parent.location.reload();  
            });          
        },
        dispatch: function () {
            $(document).on('click', '.btn-dispatch', function () {
                Fast.api.close();
                window.parent.location.reload();  
            });          
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});