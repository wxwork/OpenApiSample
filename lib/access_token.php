<?php

require_once "helper.php";

class AccessToken {
    private $corpId;     
    private $secret;     
    private $agentId;    
    private $appConfigs; 

    /**
     * AccessToken构造器
     * @param [Number] $agentId 两种情况：1是传入字符串“txl”表示获取通讯录应用的Secret；2是传入应用的agentId
     */
    public function __construct($agentId) {
        $this->appConfigs = loadConfig();
        $this->corpId = $this->appConfigs->CorpId;

        $this->secret = "";
        $this->agentId = $agentId;

        //由于通讯录是特殊的应用，需要单独处理
        if($agentId == "txl"){
            $this->secret = $this->appConfigs->TxlSecret;
        }else{
            $config = getConfigByAgentId($agentId);          

            if($config){
                $this->secret = $config->Secret;                                
            }
        }        
    }

    public function getAccessToken() {
      
        //TODO: access_token 应该全局存储与更新，以下代码以写入到文件中做示例      
        //NOTE: 由于实际使用过程中不同的应用会产生不同的token，所以示例按照agentId做为文件名进行存储
        
        $path = "../cache/$this->agentId.php";
        $data = json_decode(get_php_file($path));

        if($data->expire_time < time()) {    

            $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->corpId&corpsecret=$this->secret";                      
            $res = json_decode(http_get($url)["content"]);

            $access_token = $res->access_token;
            
            if($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;        
                set_php_file($path, json_encode($data));
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;    
    }
}

