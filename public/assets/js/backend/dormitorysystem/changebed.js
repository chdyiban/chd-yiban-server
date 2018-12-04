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
            
            // $('.selectpicker').on('show.bs.select',function(e, clickedIndex, isSelected, previousValue){
            //     console.log(e);
            //     console.log(clickedIndex);
            //     console.log(isSelected);
            //     console.log(previousValue);
            // });
            $('#search-user input').bind('input propertychange',function(){
                //alert('atere');
                var key = $(this).val();

            });

            $("#search").on('click',function(){
                Fast.api.open("./dormitorysystem/changebed/search", "搜索", {
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


       
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        },
    };   
    return Controller;
});