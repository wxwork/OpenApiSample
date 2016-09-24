var list = debug_info.debug_list;

var optionTpl = $('#optionTpl').html();
var optionFun = template.compile(optionTpl);

var argumentTpl = $('#argumentTpl').html();
var argumentFun = template.compile(argumentTpl);

var resultTpl = $('#resultTpl').html();
var resultFun = template.compile(resultTpl);

var nameEl = $('#interfaceName');
var interFaceEl = $('#interfaceList');
var methodTypeEl = $('#methodType');
var argumentEl = $('#argumentList');
var formEl = $('#postform');
var submitEl = $('#submit');
var resultEl = $('#result');
var ajaxReponseEl = $('#ajaxReponse');
var pointEl = $('#point');

var currentNameIndex = 0;
var currentInterfacesIndex = 0;

function setName() {
    nameEl.html(optionFun({
        list: list
    }));
}
setName();

function setInterFace(nameIndex) {
    interFaceEl.html(optionFun({
        list: list[nameIndex].interfaces
    }));
}
setInterFace(currentNameIndex);

function setArgument(nameIndex, interfacesIndex) {
    var _list = list[nameIndex].interfaces[interfacesIndex].arguments;
    argumentEl.html(argumentFun({
        list: _list
    }));
    methodTypeEl.html('方法：' + list[nameIndex].interfaces[interfacesIndex].method);
}
setArgument(currentNameIndex, currentInterfacesIndex);

function setResponese(resp, header, xml) {
    var interfaces = list[currentNameIndex].interfaces[currentInterfacesIndex];
    var name = list[currentNameIndex].title + '：' + interfaces.title;

    var dom = $(resultFun({
        name: name,
        url: formEl.data('action'),
        header: header,
        resp: resp,
        xml: xml,
        isErr: resp.errcode && resp.errcode !== 0,
        errMsg: resp.errmsg
    }))

    resultEl.prepend(dom);
}

function argInUrl(name) {
    var _arg = {
        'access_token': true,
        'type': true
    };
    return _arg[name];
}

nameEl.change(function() {
    var selectEl = $( "option:selected",$(this));
    currentNameIndex = parseInt(selectEl.val());
    currentInterfacesIndex = 0;
    setInterFace(currentNameIndex);
    setArgument(currentNameIndex, currentInterfacesIndex);
})

interFaceEl.change(function() {
    var selectEl = $( "option:selected",$(this));
    currentInterfacesIndex = parseInt(selectEl.val());
    setArgument(currentNameIndex, currentInterfacesIndex);
})

submitEl.on('click', function(event) {
    var interfaces = list[currentNameIndex].interfaces[currentInterfacesIndex];
    var method = interfaces.method;
    var url = interfaces.url;
    var initUrl = false;
    var args = interfaces.arguments;
    var requestData = {};
    var actionUrl = './devhandler.php?tid=' + interfaces.id || 123;
    var isMedia = false;
    var specialURL = false;
    for(var i = 0, len = args.length; i < len; i++) {
        var arg = args[i];
        var val =$.trim($('.js_' + arg.name, argumentEl).val());
        if(arg.must && !val) {
            alert('参数 ' + arg.name + ' 必填');
            event.preventDefault();
            return;
        }
        if(arg.name !== 'media') {
            actionUrl += '&' + arg.name + '=' + encodeURIComponent(val);
        } else {
            isMedia = true;
        }
        if(arg.name === 'URL') {
            specialURL = val;//encodeURIComponent(val);
        }
        if(argInUrl(arg.name) || method === 'GET') {
            url += (initUrl? '&': '?') + arg.name + '=' + encodeURIComponent(val);
            initUrl = true;
        }
    }
    if(isMedia) {
        formEl.attr('enctype',"multipart/form-data");
    } else {
        formEl.removeAttr('enctype');
    }
    if(specialURL) {
        formEl.data('action', specialURL);
    } else {
        formEl.data('action', url);
    }
    formEl.attr('action', actionUrl);

    if(!isMedia){
        if(interfaces.id == 19  && window.XMLHttpRequest){
            var xhr = new XMLHttpRequest();
            xhr.open('POST', actionUrl + '&f=json');
            xhr.responseType = 'blob';
            xhr.send();
            return;
        }
        $.ajax({
            url: actionUrl + '&f=json',
            type: 'POST',
            dataType: 'json',
            data: formEl.serialize(),
            success: function(resp, header, xml){
                setResponese(resp.content, resp.header, resp.xml);
            },
            error: function(){
                alert('系统错误');
            }
        });

        return false;
    }
});


// 原本为使用debugresp.html作回调显示结果，现在改为ajax
function showResult(resp, header, xml) {
    setResponese(JSON.stringify(resp), header, xml);
}

$('body').on('click', '.js_close', function(event) {
    var tragetEl = $(event.target);
    var p = tragetEl.closest('.js_body');
    p.fadeOut({
        complete: function() {
            p.remove();
        }
    })
})