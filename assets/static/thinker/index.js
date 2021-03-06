layui.extend({
    admin:'lay/modules/admin'
}).define(['admin','conf'],function(exports){
    //解决 IE8 不支持console
    window.console = window.console || (function () {
        var c = {}; c.log = c.warn = c.debug = c.info = c.error = c.time = c.dir = c.profile
        = c.clear = c.exception = c.trace = c.assert = function () { };
        return c;
    })();
    //初始化界面
    layui.admin.initPage();
    //对外接口
    exports('index',{});
});