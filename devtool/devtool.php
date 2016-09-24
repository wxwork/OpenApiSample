<?php
  require_once "../lib/helper.php";

  $api_config = json_decode(get_php_file("./api_config.php"));
?>

<!DOCTYPE html>
<html style="height:100%">
    <head lang="en">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <meta http-equiv="X-UA-Compatible" content="chrome=1">
        <meta name="renderer" content="webkit">        
        <title>企业微信接口调试工具</title>
        <link rel="shortcut icon" href="./assets/image/favicon.ico">        
        <link rel="stylesheet" href="./assets/css/dev-center.css">
    </head>
    <body style="height:100%">
        <div class="mod-head">            
        </div>
        <div class="mod-main ui-mb-large" style="min-height:80%">
            <h1 class="mod-main__inner-title">企业微信接口调试工具</h1>
            <div class="mod-main__inner mod-main__inner_m-medium mod-debug-tool">
                <div>此工具旨在帮助开发者检测调用【企业微信开放平台API】时发送的请求参数是否正确，提交相关信息后可获得服务器的验证结果</div>
                <div>
                    <p>使用说明：</p>
                    <ol>                    
                        <li>> 你可以直接在文本框内填入对应的参数值（红色星号表示该字段必填），点击检查问题按钮，即可得到相应的调试信息。</li>
                    </ol>
                    <p>其他工具：</p>
                    <ol>                    
                        <li>> jsapi_ticket验证工具：<a href="http://open.work.weixin.qq.com/wwopen/jsapisign" target="_blank">http://open.work.weixin.qq.com/wwopen/jsapisign</a></li>
                        <li>> jsapi demo演示地址(企业微信里面访问)：<a href="http://open.work.weixin.qq.com/wwopen/jsapidemo" target="_blank">http://open.work.weixin.qq.com/wwopen/jsapidemo</a></li>
                    </ol>
                </div>
                <div class="mod-debug-tool__hr"></div>
                <div>
                    <div class="mod-debug-tool__item">
                        <label class="mod-debug-tool__label">一、接口类型</label>
                        <div class="mod-debug-tool__box">
                            <select name="" id="interfaceName" class="mod-debug-tool__select"></select>
                        </div>
                    </div>
                    <div class="mod-debug-tool__item">
                        <div class="mod-debug-tool__label">二、接口列表</div>
                        <div class="mod-debug-tool__box">
                            <select name="" id="interfaceList" class="mod-debug-tool__select"></select>
                            <span id="methodType" class="frm_tips"></span>
                        </div>
                    </div>
                    <div class="mod-debug-tool__item">
                        <div class="mod-debug-tool__label">三、参数列表</div>
                        <div class="mod-debug-tool__box">&nbsp;</div>
                    </div>
                    <form method="POST" target="result-iframe" id="postform">
                        <div id="argumentList"></div>
                        <div class="mod-debug-tool__item mod-debug-tool__item_par">
                            <label class="mod-debug-tool__label">&nbsp;</label>
                            <div class="mod-debug-tool__box">
                                <button class="mod-debug-tool__btn" type="submit" id="submit">检查问题</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div id="result"></div>
            </div>
        </div>
        <div style="display:none">
            <iframe frameborder="no" scrolling="no" marginheight="0" marginwidth="0" id="result-iframe" name="result-iframe"></iframe>
        </div>
        <script id="optionTpl" type="text/html">
            {{each list}}
    <option value={{$index}}>{{$value.title}}</option>;
    {{/each}}
        </script>
        <script id="argumentTpl" type="text/html">
            {{each list}}
    <div class="mod-debug-tool__item mod-debug-tool__item_par">
        <div class="mod-debug-tool__label">
            {{if $value.must}}<span class="mod-debug-tool__required">*</span>{{/if}}{{$value.name}}
        </div>
        <div class="mod-debug-tool__box">
        {{if $value.type === 'file'}}
            <input type="file" name="media" class="js_{{$value.name}}"></input>
        {{else if $value.type === 'select'}}
            <select class="mod-debug-tool__select js_{{$value.name}}">
            {{each $value.value as name i}}
                <option value={{name}}>{{name}}</option>
            {{/each}}
            </select>
        {{else}}
            <textarea class="mod-debug-tool__input js_{{$value.name}}" style="height: 50px;width:591px"></textarea>
        {{/if}}
        <div class="mod-debug-tool__input-tip">{{$value.desc}}</div>
        </div>
    </div>
    {{/each}}
        </script>
        <script id="resultTpl" type="text/html">
            <div class="js_body">
    <div class="mod-debug-tool__hr"></div>
    <div class="mod-debug-tool__result">
        <a class="mod-debug-tool__result-close js_close" href="javascript:;"></a>
        <div class="mod-debug-tool__result-row" style="text-indent: 80px;">{{name}}</div>
        <div class="mod-debug-tool__result-row">
            <div class="mod-debug-tool__result-label">请求地址：</div>
            <div class="mod-debug-tool__result-box">{{url}}</div>
        </div>
        {{if header}}
        <div class="mod-debug-tool__result-row">
            <div class="mod-debug-tool__result-label">返回结果：</div>
            <div class="mod-debug-tool__result-box">{{#header}}</div>
        </div>
        <div class="mod-debug-tool__result-row">
            <div class="mod-debug-tool__result-label"></div>
            <div class="mod-debug-tool__result-box">{{resp}}</div>
        </div>
        {{else}}
        <div class="mod-debug-tool__result-row">
            <div class="mod-debug-tool__result-label">返回结果：</div>
            <div class="mod-debug-tool__result-box">{{resp}}</div>
        </div>
        {{/if}}
        {{if xml}}
        <div class="mod-debug-tool__result-row">
            <div class="mod-debug-tool__result-label"></div>
            <div class="mod-debug-tool__result-box">{{#xml}}</div>
        </div>
        {{/if}}
        {{if isErr}}
        <div class="mod-debug-tool__result-row">
            <div class="mod-debug-tool__result-label">提示：</div>
            <div class="mod-debug-tool__result-box" >
                <span style="color: #EC3D31">{{errMsg}}</span>
            </div>
        </div>
        {{/if}}
    </div>
</div>
        </script>
        <script type="text/javascript">
            var debug_info = <?php echo json_encode($api_config) ?>;
        </script>
        <script type="text/javascript" src="./assets/js/jquery-1.11.1.min.js"></script>
        <script type="text/javascript" src="./assets/js/template.min.js"></script>
        <script type="text/javascript" src="./assets/js/devtool.js"></script>
    </body>
</html>
