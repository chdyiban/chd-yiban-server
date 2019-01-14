define(['jquery', 'bootstrap', 'backend', 'table', 'form','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {
    
    var Controller = {
        index: function () {
            // console.log(param);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dormitorysystem/dormitorylist/index',
                    add_url: 'dormitorysystem/dormitorylist/add',
                    //edit_url: 'bx/repairlist/edit',
                    edit_url: 'dormitorysystem/dormitorylist/edit',
                    del_url: '',
                    multi_url: 'dormitorysystem/dormitorylist/multi',
                    free_bed_url: 'dormitorysystem/dormitorylist/freebed',
                    table: 'dormitory_list',
                }
            });

            var table = $("#table");

            table.on('load-success.bs.table', function (e,value,data) {
                var bedIdlist = {};
                $.each(value.rows,function (i,v) {
                        bedIdlist[i] = v.ID;
                    })
                $.ajax({
                    
                        type:'POST',
                        url:$.fn.bootstrapTable.defaults.extend.free_bed_url,
                        data:{
                            key: JSON.stringify(bedIdlist),
                        },
                        success:function(data){
                            $("#table tbody tr").each(function(i,v){
                                data_index = $(this).attr('data-index');
                                $(this).find("td:eq(6)").html(data[i].situation);
                                $(this).find("td:eq(7)").html(data[i].fullBedNum + "/" + data[i].allBedNum);
                            });
                        }
                    })
                //这里可以获取从服务端获取的JSON数据
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'ID',
                //sortName: 'ID',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'ID', title: __('ID'),visible:false },
                        {field: 'XQ', title: __('校区')},
                        {field: 'LH', title: __('楼号'),sortable:true,width:60},                   
                        {field: 'LC', title: __('楼层'),sortable:true,width:60},
                        {field: 'LD', title: __('楼段')},
                        {field: 'SSH', title: __('宿舍号'),sortable:true,width:80},
                        {field: 'RZQK', title: __('入住情况'),operate:false,formatter:function(value,row,index){}},
                        {field: 'RZBL', title: __('入住比例(入住/总床位)'),operate:false,formatter:function(value,row,index){}},
                        {field: 'XBDM', title: __('类别'),searchList: {"1":__('男宿'),"2":__('女宿')},formatter:function(value,row){
                            if (row.status == 1) {     
                                if(value == 1) 
                                    return "男宿";
                                if(value == 2) 
                                    return "女宿";
                            } 
                        }},
                        {field: 'status', title: __('房间属性'),searchList: {"1":__('学生用房'),"2":__('公共房'),"0":__('无法使用')},formatter:function(value){
                            if (value == 1) 
                                return "学生用房";
                            if (value == 2) 
                                return "公用房";
                            if (value == 0) 
                                return "无法使用";
                        }},
                        {field: 'operate', width: "160px", title: __('Operate'), table: table, events: Table.api.events.operate,  
                        
                            buttons: [
                                    {name: 'dormitoryinfo', title: __('查看宿舍信息'), classname: 'btn btn-xs btn-primary btn-success btn-dormitory  btn-dialog', icon: 'fa fa-gear', url: 'dormitorysystem/dormitorylist/dormitoryinfo?LH={LH}&SSH={SSH}',text: __('操作'), callback: function (data){}},      
                                    {name: 'delete', title: __('删除'), classname: 'btn  btn-xs btn-primary btn-danger  btn-ajax', icon: 'fa fa-trash', url: 'dormitorysystem/dormitorypubliclist/delete',text: __('删除'),confirm:"确定删除", success: function (data){
                                        $(".btn-refresh").trigger("click");
                                    }},      
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
        confirmdistribute:function () {
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
                                            var result = "";
                                            result = value.XH + "-" + value.XM + "-" + value.XB + "-" + value.YXJC;
                                            str = "<option value=" + result + ">" + result + "</option>";
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
                                            var result = "";
                                            result = value.XH + "-" + value.XM + "-" + value.XB + "-" + value.YXJC;
                                            str = "<option value=" + result + ">" + result + "</option>";
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
                                    var result = "";
                                    result = value.XH + "-" + value.XM + "-" + value.XB + "-" + value.YXJC;
                                    str = "<option value=" + result + ">" + result + "</option>";
                                    $('#option').append(str);
                                });
                                $('#option').selectpicker('render');
                                $('#option').selectpicker('refresh');
                                clearTimeout(timeOut);
                            }
                        });
                    },100);
                }
            });

            // $("#search").on('click',function(){
            //     Fast.api.open("./dormitorysystem/confirmdistribute/search", "搜索", {
            //             callback:function(value){
            //                 //window.location.reload();
            //                 msg = value.XH + "-" + value.XM + "-" +value.XB;
            //                 $('#userInfo').val(msg);
            //                 //在这里可以接收弹出层中使用`Fast.api.close(data)`进行回传的数据
            //             }
            //         });
            //     });
            //取消分配
            $('.cancel').on('click',function () {
                Fast.api.close();
            });
            //确定分配
            $('#confirmdistribute').on('click',function () {
                var info = $('#option').val();
                var infoarray = info.split('-');
                var postdata = {
                    'LH':$('#LH').text(),
                    'CH':$('#CH').text(),
                    'SSH':$('#SSH').text(),
                    'reason':$("input[name='reason']:checked").val(),
                    'XH' : infoarray[0],
                    'XM' : infoarray[1],
                    'XB' : infoarray[2],
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

        

        confirmdelete: function(){
            //时间选择插件
            //http://www.daterangepicker.com
            var now = new Date();
            var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
            $('#selectstarttime').daterangepicker({
                "singleDatePicker": true,
                "startDate": time,
            }, );

            $('#selectendtime').daterangepicker({
                "singleDatePicker": true,
                "startDate": time,
            }, );
            $('#confirmdelete').on('click',function () {
                var mymessage=confirm("确定要移除该床位学生吗？");
                if(mymessage == true){
                   var postdata = {
                    'LH':$('#LH').text(),
                    'CH':$('#CH').text(),
                    'SSH':$('#SSH').text(),
                    'XH':$('#XH').text(),
                    'reason':$("input[name='reason']:checked").val(),
                    'remark':$('#remark').val(),
                    'handletime':$('#selectstarttime').val(),
                    'handleendtime':$('#selectendtime').val(),
                }
                    $.ajax({
                        type: 'POST',
                        url: './dormitorysystem/Dormitorylist/deleteStuRecord',
                        data: postdata,
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
            $('.cancel').on('click',function () {
                Fast.api.close();
            });
        },

        confirmchange:function () {
            //时间选择模块
            var now = new Date();
            var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
            $('#selecthandletime').daterangepicker({
                "singleDatePicker": true,
                "startDate": time,
            }, );
            //搜索模块
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
                                            var result = "";
                                            result = value.XH + "-" + value.XM + "-" + value.XB + "-" + value.YXJC;
                                            str = "<option value=" + result + ">" + result + "</option>";
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
                                            var result = "";
                                            result = value.XH + "-" + value.XM + "-" + value.XB + "-" + value.YXJC;
                                            str = "<option value=" + result + ">" + result + "</option>";
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
                                    var result = "";
                                    result = value.XH + "-" + value.XM + "-" + value.XB + "-" + value.YXJC;
                                    str = "<option value=" + result + ">" + result + "</option>";
                                    $('#option').append(str);
                                });
                                $('#option').selectpicker('render');
                                $('#option').selectpicker('refresh');
                                clearTimeout(timeOut);
                            }
                        });
                    },100);
                }
            });

            // $("#search").on('click',function(){
            //     Fast.api.open("./dormitorysystem/confirmdistribute/search", "搜索", {
            //             callback:function(value){
            //                 //window.location.reload();
            //                 msg = value.XH + "-" + value.XM + "-" +value.XB;
            //                 $('#userInfo').val(msg);
            //                 //在这里可以接收弹出层中使用`Fast.api.close(data)`进行回传的数据
            //             }
            //         });
            //     });
            //取消分配
            $('.cancel').on('click',function () {
                Fast.api.close();
            });
            //确定分配
            $('#confirmchange').on('click',function () {
                var info = $('#option').val();
                var infoarray = info.split('-');

                $.ajax({
                    type: 'POST',
                    url: './dormitorysystem/Dormitorylist/getStuDormitory',
                    data: {'XH':infoarray[0]},
                    success: function(data) {
                        var dormitory = "";
                        dormitory =  data.data.XQ + "-" + data.data.LH + "#" + data.data.SSH + "-" + data.data.CH;
                        if (data.status) {
                            var ischange = confirm("是否确认将宿舍为" + dormitory  +"学生与此床位调换");
                            if (ischange) {
                                var postdata = {
                                    'oldLH':$('#LH').text(),
                                    'oldCH':$('#CH').text(),
                                    'oldSSH':$('#SSH').text(),
                                    'oldXH' :  $('#XH').text(),
                                    'newLH' : data.data.LH,
                                    'newSSH' : data.data.SSH,
                                    'newCH' : data.data.CH,
                                    'newXH' : infoarray[0],
                                    'remark':$('#remark').val(),
                                    'handletime':$('#selecthandletime').val(),
                                }
                                $.ajax({
                                    type: 'POST',
                                    url: './dormitorysystem/Dormitorylist/addChangeRecord',
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
                            }
                        } else {
                            alert('调换学生未安排住宿，请检查！');
                        }
                    }
                });

            
            });
        },

        add: function () {
            $('#roomStatus').change(function(){
                var roomStatus = $('#roomStatus').val();
                if  (roomStatus == '1'){
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').removeClass('hidden');
                    //为相应表单开启不验证
                    $('#c-roomName').attr('novalidate','true');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                } else if(roomStatus == '2'){
                    $('.roomName').removeClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-roomName').removeAttr('novalidate');
                    $('#c-XB').attr('novalidate','true');
                    $('#c-CWS').attr('novalidate','true');
                } else if(roomStatus == '0') {
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                    $('#c-roomName').removeAttr('novalidate');
                }
            });
            Controller.api.bindevent();
        },

        edit: function () {
            $('#roomStatus').change(function(){
                var roomStatus = $('#roomStatus').val();
                if  (roomStatus == '1'){
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').removeClass('hidden');
                    //为相应表单开启不验证
                    $('#c-roomName').attr('novalidate','true');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                } else if(roomStatus == '2'){
                    $('.roomName').removeClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-roomName').removeAttr('novalidate');
                    $('#c-XB').attr('novalidate','true');
                    $('#c-CWS').attr('novalidate','true');
                } else if(roomStatus == '0') {
                    $('.roomName').addClass('hidden');
                    $('.stuRoom').addClass('hidden');
                    $('#c-XB').removeAttr('novalidate');
                    $('#c-CWS').removeAttr('novalidate');
                    $('#c-roomName').removeAttr('novalidate');
                }
            });
            Controller.api.bindevent();
        },
        
        api: {
            //不验证被隐藏的字段参考https://forum.fastadmin.net/thread/1203
            bindevent: function () {
                // $('form[role=form]').validator({
                //     ignore: ':hidden'
                // });
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});