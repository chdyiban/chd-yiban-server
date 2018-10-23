define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/dormitorylist/index',
                    add_url: 'dormitorysystem/dormitorylist/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: '0',
                    del_url: '0',
                    multi_url: 'dormitorysystem/dormitorylist/multi',
                    free_bed_url: 'dormitorysystem/dormitorylist/freebed',
                    table: 'dormitory_list',
                }
            });

            var table = $("#table");
            // 初始化表格
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'ID',
                sortName: 'ID',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'ID', title: __('ID'),sortable:true,width:50},
                        {field: 'XQ', title: __('校区')},
                        {field: 'LH', title: __('楼号'),sortable:true,width:60},                   
                        {field: 'LC', title: __('楼层'),sortable:true,width:60},
                        {field: 'LD', title: __('楼段')},
                        {field: 'SSH', title: __('宿舍号'),sortable:true,width:80},
                        {field: 'RZQK', title: __('入住情况'),formatter:function(value,row,index){
                            var result = '';
                            $.ajax({
                                type:'POST',
                                //修改为同步请求
                                async:false,
                                url:$.fn.bootstrapTable.defaults.extend.free_bed_url,
                                data:{
                                    'key': row['ID'],
                                    'type': 'situation',
                                },
                                success:function(data){
                                    result = '';
                                    $.each(data,function (i,v) {
                                        result = result + v;
                                    })
                                }
                            })
                            return result;
                        }},
                        {field: 'RZBL', title: __('入住比例(入住/总床位)'),formatter:function(value,row,index){
                            $.ajax({
                                type:'POST',
                                //修改为同步请求
                                async:false,
                                url:$.fn.bootstrapTable.defaults.extend.free_bed_url,
                                data:{
                                    'key': row['ID'],
                                    'type': 'proportion',
                                },
                                success:function(data){
                                    var all = data.allBedNum;
                                    var full = data.fullBedNum;
                                    result =  full + '/' + all;
                                    //console.log(all + '/' + full);
                                }
                            })
                            return result;
                        }},
                        {field: 'XBDM', title: __('类别'),searchList: {"1":__('男'),"2":__('女')},formatter:function(value){
                            if(value == 1) 
                                return "男宿";
                            if(value == 2) 
                                return "女宿";
                        }},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                        buttons: [
                                {name: 'stuinfo', title: __('查看宿舍信息'), classname: 'btn btn-xs btn-primary btn-success btn-dormitory  btn-dialog', icon: 'fa fa-hand-stop-o', url: 'dormitorysystem/dormitorylist/dormitoryinfo?LH={LH}&SSH={SSH}', callback: function (data){}},      
                            ],     
                        formatter: Table.api.formatter.operate,               
                    }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');

        },
        dormitoryinfo:function () {
            //分配方法
            // $(document).on('click', '.btn-confirmdistribute', function (value) {
            //     var dormitory = $("#dormitory").text();
            //     var dormitorylist = dormitory.split('-');
            //     var LH = dormitorylist[0];
            //     var SSH = dormitorylist[1];
            //     var info = value.target.value;
            //     var infolist = info.split('-');
            //     var CH = infolist[0];
            //     var XH = infolist[1];
            //     $.ajax({
            //         type: 'POST',
            //         url: './dormitorysystem/Dormitorylist/delete',
            //         data: {
            //             'XH':XH,
            //             'LH':LH,
            //             'SSH':SSH,
            //             'CH':CH,
            //         },
            //         success: function(data) {
            //             if (data === 1) {
            //                 //Fast.api.close();
            //                 //alert("移除成功！")
            //                 //window.location.reload();  
            //             }
            //         }
            //     });
                
            // });  
        },

        confirmdelete: function(){
            $(document).on('click', '.btn-confirmdelete', function () {
                var mymessage=confirm("确定要移除该床位学生吗？");
                if(mymessage == true){
                    var reason = $('#reason').val();
                    var remark = $('#remark').val();
                    var LH = $('#LH').text();
                    var SSH = $('#SSH').text();
                    var CH = $('#CH').text();
                    var XH = $('#XH').text();
                    $.ajax({
                        type: 'POST',
                        url: './dormitorysystem/Dormitorylist/deleteStuRecord',
                        data: {
                            'XH':XH,
                            'LH':LH,
                            'SSH':SSH,
                            'CH':CH,
                            'reason':reason,
                            'remark':remark,
                        },
                        success: function(data) {
                            if (data === true) {
                                alert('移除成功');
                                Fast.api.close();
                                window.parent.location.reload();
                            } else {
                                alert('网络原因移除失败，稍后重试');
                            }
                        }
                    });
                }
            }); 
            $(document).on('click', '.btn-canceldelete', function () {
                Fast.api.close();
            }); 
        },
        confirmdistribute: function(){
            //在学号搜索界面通过学号搜索
            $(document).on('click','#search',function(){
                var XH = $('#XH').val();
                if (XH === '') {
                    alert('不可以为空！');
                } else {
                    console.log(typeof(XH));
                    //判断学号是数字还是汉字
                    //if (typeof(XH) == Number) {
                        $.ajax({
                            type: 'POST',
                            url: './dormitorysystem/Dormitorylist/searchStuByXh',
                            data: {
                                'XH':XH,
                            },
                            success: function(data) {
                                if (data.XH == "") {
                                    
                                    $('#result').removeClass('hidden');
                                    $('#error').removeClass('hidden');
                                    $('#msg').html(data.msg);
                                    $('#confirmdistribute').addClass('hidden');
                                } else {

                                    $('#result').removeClass('hidden');
                                    $('#searchbyxh').addClass('hidden');
                                    $('#success').removeClass('hidden');
                                    $('#StuXH').html(data.XH);
                                    $('#StuXM').html(data.XM);
                                    $('#StuXB').html(data.XB);
                                    $('#YXJC').html(data.YXJC);
                                    $('#YXDM').html(data.YXDM);
                                }
                            }
                        });
                    //} else {

                    //}
                }
            });


            $(document).on('click', '#confirmdistribute', function () {
                var message = confirm("是否确定要将此学生分配至该床位？");
                if (message) {
                    var LH = $('#LH').text();
                    var SSH = $('#SSH').text();
                    var CH = $('#CH').text();
                    var XH = $('#StuXH').text();
                    var YXDM = $('#YXDM').text();
                    var XB = $('#StuXB').text();
                    $.ajax({
                        type: 'POST',
                        url: './dormitorysystem/Dormitorylist/addStuRecord',
                        data: {
                            'XH':XH,
                            'LH':LH,
                            'SSH':SSH,
                            'CH':CH,
                            'YXDM':YXDM,
                            'XB':XB,
                        },
                        success: function(data) {
                            if (data.status === true) {
                                alert(data.msg);
                                Fast.api.close();
                                window.parent.location.reload();
                            } else {
                                alert(data.msg);
                                window.location.reload();
                            }
                        }
                    });
                }
            }); 
            $(document).on('click', '#reselect', function () {
                window.location.reload();
            }); 
            $(document).on('click', '.btn-canceldelete', function () {
                Fast.api.close();
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