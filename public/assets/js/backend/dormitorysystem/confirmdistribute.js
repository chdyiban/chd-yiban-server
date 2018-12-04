define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index:function(){
            //时间选择模块
            var now = new Date();
            var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
            $('#selecthandletime').daterangepicker({
                "singleDatePicker": true,
                "startDate": time,
            }, );

            $('.selectpicker').selectpicker({   
                title:'未选择',
                liveSearchPlaceholder:'请输入姓名或学号',
                maxOptions:20,
                width:'auto',
            });
            var timeOut = ""; 

            $('#search-user input').bind('input propertychange',function(){
                var key = $(this).val();
                var nj = key.substring(0,4);
                //如果前四位是数字表示是学号

                if (!isNaN(parseInt(nj))) {
                    if (nj <= 2015) {
                        if (key.length >= 11) {
                            clearTimeout(timeOut); 
                            timeOut = setTimeout(function (){                         
                                var postData = {'XH':key};
                                $('#option').empty();
                                $.ajax({
                                    type: 'POST',
                                    url: './dormitorysystem/Dormitorylist/searchStuByXh',
                                    data: postData,
                                    success: function(data) {
                                        var str = "";  
                                        $.each(data, function(key, value) {
                                            str = "<option value=" + value.XH + ">" + value.XH + "-" + value.XM + "-" + value.YXJC + "</option>";
                                            $('#option').append(str);
                                        });
                                        $('#option').selectpicker('render');
                                        $('#option').selectpicker('refresh');
                                        clearTimeout(timeOut); 
                                    }
                                });
                            },100);
                        }
                    } else {
                        if (key.length >= 9) {
                            clearTimeout(timeOut); 
                            timeOut = setTimeout(function (){                         
                                var postData = {'XH':key};
                                $('#option').empty();
                                $.ajax({
                                    type: 'POST',
                                    url: './dormitorysystem/Dormitorylist/searchStuByXh',
                                    data: postData,
                                    success: function(data) {
                                        var str = "";  
                                        $.each(data, function(key, value) {
                                            str = "<option value=" + value.XH + ">" + value.XH + "-" + value.XM + "-" + value.YXJC + "</option>";
                                            $('#option').append(str);
                                        });
                                        $('#option').selectpicker('render');
                                        $('#option').selectpicker('refresh');
                                        clearTimeout(timeOut); 
                                    }
                                });
                            },100);
                        } 
                    } 
                } else if(key != '' && key != null) {
                    clearTimeout(timeOut); 
                    timeOut = setTimeout(function (){                         
                        var postData = {'name':key};
                        $('#option').empty();
                        $.ajax({
                            type: 'POST',
                            url: './dormitorysystem/Dormitorylist/searchStuByName',
                            data: postData,
                            success: function(data) {
                                var str = "";  
                                $.each(data, function(key, value) {
                                    str = "<option value=" + value.XH + ">" + value.XH + "-" + value.XM + "-" + value.YXJC + "</option>";
                                    $('#option').append(str);
                                });
                                $('#option').selectpicker('render');
                                $('#option').selectpicker('refresh');
                                clearTimeout(timeOut);
                                console.log(str); 
                            }
                        });
                    },100);
                }
            });

            $("#search").on('click',function(){
                Fast.api.open("./dormitorysystem/confirmdistribute/search", "搜索", {
                        callback:function(value){
                            //window.location.reload();
                            msg = value.XH + "-" + value.XM + "-" +value.XB;
                            $('#userInfo').val(msg);
                            //在这里可以接收弹出层中使用`Fast.api.close(data)`进行回传的数据
                        }
                    });
                });
            //取消分配
            $('.cancel').on('click',function () {
                Fast.api.close();
            });
            //确定分配
            $('#confirmdistribute').on('click',function () {

                var postdata = {
                    'LH':$('#LH').text(),
                    'CH':$('#CH').text(),
                    'SSH':$('#SSH').text(),
                    'reason':$("input[name='reason']:checked").val(),
                    'info':$('#userInfo').val(),
                    'remark':$('#remark').val(),
                    'newclass':$('#newclass').val(),
                    'handletime':$('#selecthandletime').val(),
                }

                $.ajax({
                    type: 'POST',
                    url: './dormitorysystem/Dormitorylist/addStuRecord',
                    data: postdata,
                    success: function(data) {
                        if (data.status == true) {
                            alert(data.msg);
                            Fast.api.close();
                            window.parent.location.reload();
                        } else{
                            alert(data.msg);
                        }
                    }
                });
            });
            
        },


        search: function () {
            // console.log(param);
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/confirmdistribute/search',
                    add_url: 'dormitorysystem/confirmdistribute/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: '0',
                    del_url: '0',
                    multi_url: 'dormitorysystem/confirmdistribute/multi',
                    table: 'dormitory_list',
                }
            });

            var table = $("#table");

            // 初始化表格
             table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                // pk: 'ID',
                // sortName: 'ID',
                //关闭快速搜索模式
                search:false,
                //取消浏览模式
                showToggle: false,
                //取消显示隐藏列
                showColumns: false,
                //取消导出
                showExport: false,
                searchFormVisible: true,
                searchFormTemplate: 'customformtpl',
                columns: [
                    [                    
                        // {checkbox: true},
                        {field: 'XH', title: __('学号'),sortable:true},
                        {field: 'XM', title: __('姓名')},
                        {field: 'XB', title: __('性别')},
                        {field: 'YXJC', title: __('学院')},                   
                        {field: 'YXDM', title: __('学院'), visible: false},                   
                        {field: 'NJ', title: __('年级'),sortable:true},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                        buttons: [
                                {
                                    name: 'conifrmdistribute', 
                                    title: __('确定'), 
                                    classname: 'btn  btn-success btn-sure', 
                                    icon: 'fa fa-hand-stop-o',
                                    hidden:function(row){
                                        //console.log(row.length);
                                        if (row.length === 0) {
                                            return true;
                                        }
                                    },
                                },      
                            ],
                        //参考材料https://forum.fastadmin.net/thread/1762
                        //以及参考https://forum.fastadmin.net/thread/6658
                        //进行自定义按钮方法
                        events: Controller.api.events.operate,

                        formatter: Controller.api.formatter.operate,               
                    }
                    ]
                ]
            });
            
            // 为表格绑定事件
            Table.api.bindevent(table);
            //取消双击编辑
            table.off('dbl-click-row.bs.table');
    
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },

            events:{
                operate:{
                    'click .btn-sure':function (e,value,row,index) {
                        //var message = confirm("是否确认将此学生分配至该床位？");
                        //if (message) {
                            var data = {'XH':row.XH,'XB':row.XB,'XM':row.XM};
                            Fast.api.close(data);
                            // var LH = $('#LH').text();
                            // var CH = $('#CH').text();
                            // var SSH = $('#SSH').text();
                            // var XH = row.XH;
                            // var XB = row.XB;
                            // var YXDM = row.YXDM;
                            // $.ajax({
                            //     type: 'POST',
                            //     //url: './dormitorysystem/Dormitorylist/addStuRecord',
                            //     data: {
                            //         'XH':XH,
                            //         'LH':LH,
                            //         'SSH':SSH,
                            //         'CH':CH,
                            //         'XB':XB,
                            //         'YXDM':YXDM,
                            //     },
                            //     success: function(data) {
                            //         if (data.status == true) {
                            //             alert(data.msg);
                            //             Fast.api.close();
                            //             window.parent.location.reload();
                            //         } else{
                            //             alert(data.msg);
                            //         }
                            //     }
                            // });
                        //}
                    }
                }
            },

            formatter:{
                operate:function (value,row,index) {
                    var table = this.table;
                    //操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    //默认按钮组
                    var buttons = $.extend([], this.buttons || []);

                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                }
            }
        },
    };   
    return Controller;
});