define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
           var LH = $('#LH').text();
           var t = $('#dormitory_id');
           $.ajax({
                type: 'POST',
                url: './dormitorysystem/buildingimage/index',
                data: {'LH':LH},
                success: function(data) {
                    $.each(data, function(key, value) {
                        var room = $("td[data-id="+key+"]");
                        room.html(key+"<br>("+value.used+"/"+value.allbed+")");
                        if (value.used == 0) {
                            room.addClass("empty");
                        } else if(value.used == value.allbed) {
                            room.addClass("full");
                        } else{
                            room.addClass("used");
                        }
                        // console.log(key);
                        // console.log(value);
                    });
                }
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