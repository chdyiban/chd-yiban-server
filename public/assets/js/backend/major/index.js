define(['jquery', 'bootstrap', 'https://cdn.bootcss.com/jquery.qrcode/1.0/jquery.qrcode.min.js','backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            //判断用户是否报名
            var check = $("#check").val();
            if (check == "true") {
                var requestUrl = $("#url").val();
                $('#code').qrcode(requestUrl); //任意字符串
            } else {
                //指派单位
                $(document).on('click', '.btn-sign', function () {
                var GH = $("#GH").val();
                var XM = $("#XM").val();
                var KC = $("input[name='place']:checked").val();
                // var worker_id = $("#worker").val();
                if (typeof(KC) == "undefined") {
                    alert('请选择考场');
                } else {
                    $.ajax({
                        type: 'POST',
                        url: './major/index/index',
                        data: {
                            'GH' : GH,
                            'KC' : KC,
                            'XM' : XM,
                        },
                        success: function(data) {
                            if (data.status == true) {
                                alert("报名成功，请及时打印准考证");
                                window.location.reload();
                            } else {
                                alert(data.msg);
                            }
                        }
                    });
                }
            });  
        }
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };   
    return Controller;
});