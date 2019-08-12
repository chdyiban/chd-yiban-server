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
                    //edit_url: '0',
                    //del_url: 'bx/repairlist/del',
                    multiaccept_url: 'bx/repairlist/multiaccept',
                    multi_dispatch_url: 'bx/repairlist/multidispatch',
                    //获取维修类型
                    get_type_url: 'bx/repairlist/getTypeJson',
                    //获取服务项目
                    get_specific_url: 'bx/repairlist/getSpecificJson',
                    get_adress_url: 'bx/repairlist/getAdressJson',
                    get_company_url: 'bx/repairlist/getCompanyJson',
                    get_worker_url: 'bx/repairlist/getWorkerJson',
                    table: 'repair_list',
                }
            });

            var table = $("#table");

            // 初始化表格依据不同的status
            switch (param) {
                //全部工单
                case "all":
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
                                {field: 'phone', title: __('联系方式'),operate:false},                   
                                {field: 'title', title: __('故障描述'),operate:false},
                                {field: 'gettypename.name', title: __('服务类型名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_type_url)},
                                {field: 'gettypename.specific_name', title: __('服务项目名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_specific_url)},
                                {field: 'getaddress.name', title: __('报修区域'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_adress_url)},
                                {field: 'address', title: __('报修地点'),operate:false},
                                {field: 'submit_time', title: __('报修时间'),operate:false, addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                                {field: 'getname.nickname', title: __('受理人'),operate:false},
                                {field: 'getcompany.nickname', title: __('分配单位'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_company_url)},
                                {field: 'getworkername.name', title: __('工人姓名'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_worker_url)},
                                {field: 'getworkername.mobile', title: __('联系方式'),operate:false},
                                {field: 'refused_content', title: __('拒绝原因'),operate:false},
                                {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                                {field: 'status_text', title: __('Status'), operate:false},
                                {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                                
                                buttons: [
                                        {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                                    ],     
                                formatter: Table.api.formatter.operate,                  
                            }
                            ]
                        ]
                    });
                    break;
                //未受理订单
                case "waited":
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
                                {field: 'phone', title: __('联系方式'),operate:false},                   
                                {field: 'title', title: __('故障描述'),operate:false},
                                {field: 'gettypename.name', title: __('服务类型名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_type_url)},
                                {field: 'gettypename.specific_name', title: __('服务项目名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_specific_url)},
                                {field: 'getaddress.name', title: __('报修区域'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_adress_url)},
                                {field: 'address', title: __('报修地点'),operate:false},
                                {field: 'submit_time', title: __('报修时间'),operate: false, addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                                {field: 'getname.nickname', title: __('受理人'),visible:false,operate:false},
                                {field: 'getcompany.nickname', title: __('分配单位'),visible:false,operate:false},
                                {field: 'refused_content', title: __('拒绝原因'),visible:false,operate:false},
                                {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                                {field: 'status_text', title: __('Status'), operate:false},
                                {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                                
                                buttons: [
                                        {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                                    ],     
                                formatter: Table.api.formatter.operate,                  
                            }
                            ]
                        ]
                    });
                    break;
                //已受理订单
                case "accepted":
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
                                {field: 'phone', title: __('联系方式'),operate:false},                   
                                {field: 'title', title: __('故障描述'),operate:false},
                                {field: 'gettypename.name', title: __('服务类型名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_type_url)},
                                {field: 'gettypename.specific_name', title: __('服务项目名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_specific_url)},
                                {field: 'getaddress.name', title: __('报修区域'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_adress_url)},
                                {field: 'address', title: __('报修地点'),operate:false},
                                {field: 'submit_time', title: __('报修时间'),operate:false, addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                                {field: 'getname.nickname', title: __('受理人'),operate:false},
                                {field: 'getcompany.nickname', title: __('分配单位'),visible:false,operate:false},
                                {field: 'refused_content', title: __('拒绝原因'),visible:false,operate:false},
                                {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                                {field: 'status_text', title: __('Status'), operate:false},
                                {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                                
                                buttons: [
                                        {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                                    ],     
                                formatter: Table.api.formatter.operate,                  
                            }
                            ]
                        ]
                    });
                    break;
                //已派工
                case "dispatched":
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
                                {field: 'phone', title: __('联系方式'),operate:false},                   
                                {field: 'title', title: __('故障描述'),operate:false},
                                {field: 'gettypename.name', title: __('服务类型名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_type_url)},
                                {field: 'gettypename.specific_name', title: __('服务项目名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_specific_url)},
                                {field: 'getaddress.name', title: __('报修区域'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_adress_url)},
                                {field: 'address', title: __('报修地点'),operate:false},
                                {field: 'submit_time', title: __('报修时间'),operate:false, addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                                {field: 'getname.nickname', title: __('受理人'),operate:false},
                                {field: 'getcompany.nickname', title: __('分配单位'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_company_url)},
                                {field: 'getworkername.name', title: __('工人姓名'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_worker_url)},
                                {field: 'getworkername.mobile', title: __('联系方式'),operate:false},
                                {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                                {field: 'status_text', title: __('Status'), operate:false},
                                {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                                
                                buttons: [
                                        {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                                        {name: 'finishDirect', text: __('直接结算'), classname: 'btn btn-xs btn-info btn-finish  btn-ajax', url: 'bx/Repairlist/finish', success: function (data, ret) {
                                            alert("结算成功");
                                            $(".btn-refresh").trigger("click"); 
                                        },},      
                                    ],     
                                formatter: Table.api.formatter.operate,                  
                            }
                            ]
                        ]
                    });
                    break;
                //已派单位
                case "distributed":
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
                                {field: 'phone', title: __('联系方式'),operate:false},                   
                                {field: 'title', title: __('故障描述'),operate:false},
                                {field: 'gettypename.name', title: __('服务类型名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_type_url)},
                                {field: 'gettypename.specific_name', title: __('服务项目名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_specific_url)},
                                {field: 'getaddress.name', title: __('报修区域'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_adress_url)},
                                {field: 'address', title: __('报修地点'),operate:false},
                                {field: 'submit_time', title: __('报修时间'),operate:false, addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                                {field: 'getname.nickname', title: __('受理人'),operate:false},
                                {field: 'getcompany.nickname', title: __('分配单位'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_company_url)},
                                {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                                {field: 'status_text', title: __('Status'), operate:false},
                                {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                                
                                buttons: [
                                        {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                                    ],     
                                formatter: Table.api.formatter.operate,                  
                            }
                            ]
                        ]
                    });
                    break;
                //已完工
                case "finished":
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
                                {field: 'phone', title: __('联系方式'),operate:false},                   
                                {field: 'title', title: __('故障描述'),operate:false},
                                {field: 'gettypename.name', title: __('服务类型名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_type_url)},
                                {field: 'gettypename.specific_name', title: __('服务项目名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_specific_url)},
                                {field: 'getaddress.name', title: __('报修区域'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_adress_url)},
                                {field: 'address', title: __('报修地点'),operate:false},
                                {field: 'submit_time', title: __('报修时间'),operate: 'RANGE', addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                                {field: 'getname.nickname', title: __('受理人'),operate:false},
                                {field: 'getcompany.nickname', title: __('分配单位'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_company_url)},
                                {field: 'getworkername.name', title: __('工人姓名'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_worker_url)},
                                {field: 'getworkername.mobile', title: __('联系方式'),operate:false},
                                {field: 'finished_time', title: __('完工时间'),operate:false,formatter: Table.api.formatter.datetime},
                                {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                                {field: 'status_text', title: __('Status'), operate:false},
                                {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                                
                                buttons: [
                                        {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/detail', callback: function (data){}},      
                                    ],     
                                formatter: Table.api.formatter.operate,                  
                            }
                            ]
                        ]
                    });
                    break;
                case "refused":
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
                                {field: 'phone', title: __('联系方式'),operate:false},                   
                                {field: 'title', title: __('故障描述'),operate:false},
                                {field: 'gettypename.name', title: __('服务类型名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_type_url)},
                                {field: 'gettypename.specific_name', title: __('服务项目名称'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_specific_url)},
                                {field: 'getaddress.name', title: __('报修区域'),searchList:$.getJSON($.fn.bootstrapTable.defaults.extend.get_adress_url)},
                                {field: 'address', title: __('报修地点'),operate:false},
                                {field: 'submit_time', title: __('报修时间'),operate:false, addclass: 'datetimerange',formatter: Table.api.formatter.datetime},
                                {field: 'getname.nickname', title: __('受理人'),visible:false,operate:false},
                                {field: 'getcompany.nickname', title: __('分配单位'),visible:false,operate:false},
                                {field: 'getcompany.nickname', title: __('工人姓名'),visible:false,operate:false},
                                {field: 'refused_content', title: __('驳回原因'),operate:false},
                                {field: 'refused_time', title: __('驳回时间',),operate:false,formatter: Table.api.formatter.datetime},
                                {field: 'status', title: __('Status'), visible:false, searchList: {"waited":__('status waited'),"accepted":__('status accepted'),"distributed":__('status distributed'),"dispatched":__('status dispatched'),"finished":__('status finished'),"refused":__('status refused')}},
                                {field: 'status_text', title: __('Status'), operate:false},
                                {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                                
                                buttons: [
                                        {name: 'detail', title: __('工单详情'), classname: 'btn btn-xs btn-primary btn-success btn-detail  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'bx/Repairlist/finish', callback: function (data){}},      
                                    ],     
                                formatter: Table.api.formatter.operate,                  
                            }
                            ]
                        ]
                    });
                    break;
            }


            // 为表格绑定事件
            Table.api.bindevent(table);

            //获取选中项
            $(document).on("click", ".btn-selected", function () {
                Layer.alert(JSON.stringify(table.bootstrapTable('getSelections')));
            });

            //批量受理
            $('.btn-multi-accept').on('click',function () {
                var ids = Table.api.selectedids(table);
                if (ids == false) {
                    alert("请选择批量处理的订单");
                } else {
                    $.ajax({
                        type: 'POST',
                        url: $.fn.bootstrapTable.defaults.extend.multiaccept_url,
                        data: {
                            'accept_ids':JSON.stringify(ids),
                        },
                        success: function(data) {
                            if (data) {
                                alert("批量受理成功！");
                                $(".btn-refresh").trigger("click"); 
                            }
                        }
                    });
                }
                
            });
            //批量派工
            //这里需要手动为Form绑定上元素事件
            Form.api.bindevent($("form#cxselectform"));
            $('.btn-multi-dispatch').on('click',function () {
                var ids = Table.api.selectedids(table);
                var company_id = $("#company").val();
                var worker_id = $("#worker").val();
                if (ids == false) {
                    alert("请选择批量派工的订单");
                } else {
                    $.ajax({
                        type: 'POST',
                        url: $.fn.bootstrapTable.defaults.extend.multi_dispatch_url,
                        data: {
                            'order_ids':  JSON.stringify(ids),
                            'company_id' : company_id,
                            'worker_id' : worker_id,
                        },
                        success: function(data) {
                            if (data == 1) {
                                alert('批量派工成功');
                                $(".btn-refresh").trigger("click"); 
                            } else {
                                alert('操作有误，请确认');
                            }
                        },
                    });
                }
                
            });
            
            table.on('load-success.bs.table', function (e,value,data) {
                $("#table tbody tr").each(function(i,v){
                    name = value.rows[i].getaddress.name;
                    $(this).find("td:eq(8)").html(name.substr(0,name.length-4));
                });
                //这里可以获取从服务端获取的JSON数据
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
            //这里需要手动为Form绑定上元素事件
            Form.api.bindevent($("form#cxselectform"));
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
                var company_id = $("#company").val();
                var worker_id = $("#worker").val();
                if (company_id == '') {
                    alert('请选择分配单位');
                } else {
                    if (worker_id == null) {
                        $.ajax({
                            type: 'POST',
                            url: './bx/Repairlist/distribute/ids/'+ids,
                            data: {
                                'company_id':company_id,
                            },
                            success: function(data) {
                                if (data == 1) {
                                    Fast.api.close();
                                    alert('单位分配成功');
                                    window.parent.location.reload();  
                                } else {
                                    alert('操作有误，请确认');
                                }
                            }
                        });
                    }  else {
                        $.ajax({
                            type: 'POST',
                            url: './bx/Repairlist/distribute/ids/'+ids,
                            data: {
                                'company_id':company_id,
                                'worker_id' : worker_id,
                            },
                            success: function(data) {
                                if (data == 1) {
                                    Fast.api.close();
                                    alert('单位以及工人分配成功');
                                    window.parent.location.reload();  
                                }
                            }
                        });
                    }
                }
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
                var worker_id = $("#worker").val();

                $.ajax({
                    type: 'POST',
                    url: './bx/Repairlist/dispatch/ids/'+ids,
                    data: {
                        'worker_id':worker_id,
                    },
                    success: function(data) {
                        if (data == 1) {
                            Fast.api.close();
                            alert("工人分配成功");
                            window.parent.location.reload();  
                        }
                    }
                });
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
                $("#refuse_text").toggleClass('hidden',false);
                $(".btn-accept").toggleClass('hidden',true);

                if ($("#refuse_content").val() == ''){
                    alert("请填写驳回理由");
               } else{
                    $.ajax({
                        type: 'POST',
                        url: './bx/Repairlist/refuse/ids/'+ids,
                        data: {
                            'refuse_content':$("#refuse_content").val(),
                        },
                        success: function(data) {
                            if (data === 1) {
                                Fast.api.close();
                                alert('已经驳回订单');
                                window.parent.location.reload();  
                            }
                        }
                    });
               }
                //window.location.href="../../refuse/ids/"+ids;
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