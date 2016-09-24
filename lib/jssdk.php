<?php

require_once "access_token.php";
require_once "helper.php";

class JSSDK {
    private $appId;  
    private $accessToken;
    private $appConfigs;

    public function __construct($agentId) {
      $this->appConfigs = loadConfig();
      $this->appId = $this->appConfigs->CorpId;         
      $this->accessToken = new AccessToken($agentId);      
    }

    public function getSignPackage() {
      $jsapiTicket = $this->getJsApiTicket();

      // 注意 URL 一定要动态获取，不能 hardcode.
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
      $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

      $timestamp = time();
      $nonceStr  = createNonceStr();

      //这里参数的顺序要按照 key 值 ASCII 码升序排序
      $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

      $signature = sha1($string);

      $signPackage = array(
          "appId"     => $this->appId,
          "nonceStr"  => $nonceStr,
          "timestamp" => $timestamp,
          "url"       => $url,
          "signature" => $signature,
          "rawString" => $string
      );
      return $signPackage; 
    }

    private function getJsApiTicket() {      
      // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
      $path = "../cache/jsapi_ticket.php";
      $data = json_decode(get_php_file($path));
      
      if($data->expire_time < time()){        
          $accessToken = $this->accessToken->getAccessToken();      
          $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";          

          $res = json_decode(http_get($url)["content"]);
          $ticket = $res->ticket;

          if ($ticket) {
              $data->expire_time = time() + 7000;
              $data->jsapi_ticket = $ticket;
              
              set_php_file($path, json_encode($data));
          }
      } else {
          $ticket = $data->jsapi_ticket;
      }

      return $ticket;
    }
}

