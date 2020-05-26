define(['jquery', 'bootstrap', 'backend', 'table', 'form','validator','bootstrap-daterangepicker','bootstrap-select'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'conversationrecord/index/index',
                    get_count_url: 'conversationrecord/index/getcount',
                    add_url: 'conversationrecord/index/add',
                    edit_url: 'conversationrecord/index/edit',
                    del_url: 'conversationrecord/index/del',
                    multi_url: 'conversationrecord/index/multi',
                    share_url: 'conversationrecord/index/share',
                    table: 'record_stuinfo',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'ID',
                sortName: 'ID',
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'ID', title: __('ID'),operate: false, visible:false},
                        {field: 'XH', title: __('学号'),operate: 'LIKE %...%'},
                        {field: 'XM', title: __('姓名'),operate: 'LIKE %...%'},
                        {field: 'tags', title: __('评价'),operate: 'LIKE %...%',formatter:function(value,row){ 
                            var flagArray = value.split(',');
                            var str = ""
                            $.each(flagArray,function(i,val){
                                str = str + "<span class=\"badge bg-blue\">"+val+"</span>"
                            })
                            return str
                        }},
                        {field: 'JDSJ', title: __('建档时间'),operate: 'RANGE', sortable:true,width:100,addclass: 'datetimerange',formatter:function(value,row){ 
                            if (value == 0) {
                                return "无";
                            } else{
                                var now = new Date(value*1000);
                                var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
                                return time; 
                            }
                        }},
                        {field: 'THCS', title: __('谈话次数'),sortable:true,width:100, },
                        {field: 'THSJ', title: __('最近谈话时间'),operate: false,formatter:function(value,row){ 
                            if (value == 0) {
                                return "无";
                            } else{
                                var now = new Date(value*1000);
                                var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();
                                return time; 
                            }
                        }},
                        {field: 'GZLX', title: __('关注类型'), visible:false,searchList: {"1":__('一般'),"2":__('重点'),"0":"取消关注"},"3":"非重点关注"},
                        {field: 'GZLX_text', title: __('关注类型'), operate:false,formatter:function(value,row){ 
                            switch (value) {
                                case "一般":
                                    str = "<span class=\"badge bg-yellow\">"+value+"</span>";
                                    return str;
                                case "重点":
                                    str = "<span class=\"badge bg-red\">"+value+"</span>";
                                    return str;
                                case "取消关注":
                                    str = "<span class=\"badge bg-blue\">"+value+"</span>";
                                    return str;
                                case "非重点关注":
                                    str = "<span>"+value+"</span>";
                                    return str;
                            }
                        }},
                        
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: __('谈话记录'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    // icon: 'fa fa-list',
                                    extend:'data-area=\'["100%","100%"]\'',
                                    text: "查看详情",
                                    url: 'conversationrecord/content/index?ID={ID}',
                                },
                                {
                                    name: 'score',
                                    title: __('查看成绩'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    // icon: 'fa fa-list',
                                    extend:'data-area=\'["100%","100%"]\'',
                                    text: "查看成绩",
                                    url: 'conversationrecord/score/index?XH={XH}',
                                }
                            ]
                        }
                    ]
                ]
            });

            //  //共享管理权限
            //  $('.btn-share').on('click',function () {
            //     var ids = Table.api.selectedids(table);
            //     if (ids == false) {
            //         alert("请选择转移管理权限的学生");
            //     } else {
            //         Fast.api.open($.fn.bootstrapTable.defaults.extend.share_url+"?stu_ids="+JSON.stringify(ids),'共享管理权限');
            //     }
                
            // });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            //时间选择模块
            window.load
            var loading = layer.load(0, {
                shade: [0.8, '#FFFFFF'],
                time: 2*10000
            });

            var now = new Date();
            var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();

            $('#option').selectpicker({   
                title:'未选择',
                liveSearchPlaceholder:'请输入姓名或学号',
                maxOptions:20,
                width:'auto',
            });

            //时间选择模块
            $('#selectInsertTime').daterangepicker({
                "singleDatePicker": true,
                "autoApply": true,
                "showDropdowns": true,
                "alwaysShowCalendars": true,
                "startDate": time,
            }, );

            $('#c-JDSJ').daterangepicker({
                singleDatePicker: true,
                startDate: time,
            }, );
            //关闭loading
            layer.close(loading);
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
                                    url: './conversationrecord/index/searchStuByXh',
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
                                    url: './conversationrecord/index/searchStuByXh',
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
                            url: './conversationrecord/index/searchStuByName',
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

            Controller.api.bindevent();
            $('#add-form').validator({
                dataFilter: function(data) {
                    if (data.status === 200) return "";
                    else return data.error;
                },
                fields: {
                    "row[info]": "required; remote(./conversationrecord/index/getStatus)"
                }
            });
            //获取学生宿舍信息
            $("#option").change(function () {
                var info = $("#option").val();
                var infoarray = info.split('-');
                var XH = infoarray[0];
                $.ajax({
                    type: 'POST',
                    url: './conversationrecord/index/getSSDM',
                    data: {"XH":XH},
                    success: function(data) {
                        $('#c-SSDM').val(data.data.dormitory);
                    }
                });
            });
            
        },
        edit: function () {
            //时间选择模块
            var now = new Date($('#selectInsertTime').val()*1000);
            var time = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();

            var dormitory = $('#c-SSDM').val();
            if (dormitory == "") {
                var XH = $("#c-XH").val();
                $.ajax({
                    type: 'POST',
                    url: './conversationrecord/index/getSSDM',
                    data: {"XH":XH},
                    success: function(data) {
                        $('#c-SSDM').val(data.data.dormitory);
                    }
                });
            }

            //时间选择模块
            $('#selectInsertTime').daterangepicker({
                "singleDatePicker": true,
                "autoApply": true,
                "showDropdowns": true,
                "alwaysShowCalendars": true,
                "startDate": time,
            }, );
            Controller.api.bindevent();
        },
        //共享管理权限
        share: function () {     
            //共享管理权限
            // $('.btn-share').on('click',function () {
            //     var ids = Table.api.selectedids(table);
            //     if (ids == false) {
            //         alert("请选择共享管理的学生");
            //     } else {
            //         Fast.api.open($.fn.bootstrapTable.defaults.extend.share_url+"?stu_ids="+JSON.stringify(ids),'共享管理权限');
            //     }
                
            // });
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