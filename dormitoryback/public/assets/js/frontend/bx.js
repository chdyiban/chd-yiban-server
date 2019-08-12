define(['jquery', 'bootstrap', 'frontend', 'form', 'template'], function ($, undefined, Frontend, Form, Template) {

    var Controller = {
        detail: function () {
            //本地验证未通过时提示
            alert(111);
            $(document).on("click", ".btn-finishWork", function () {
                // var id = "resetpwdtpl";
                // var content = Template(id, {});
                alert(1111);
            });
        },
        
    };
    return Controller;
});