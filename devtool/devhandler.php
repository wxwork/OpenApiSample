<?php
	
	require_once "../lib/helper.php";
	require_once "../lib/msgcrypt.php";
	require_once "../lib/xmlparse.php";
	
	//根据接口ID获取配置项
	function getApiConfigById($id){
		$configs = json_decode(get_php_file("./api_config.php"));

		foreach ($configs->debug_list as $key => $value) {				
			foreach ($value->interfaces as $api_key => $api_config) {
				if($api_config->id == $id){
					$detail_config = $api_config;
				}				
			}
		}
		return $detail_config;
	}
	
	//处理页面的跨域响应	
	function printCrossDomainRes($result){
	    $header = $result["header"];
	    $content = $result["content"];
	    $response = "<script type=\"text/javascript\">".
	                    "var debugresp=$content,".
	                    "debugrespheader=\"$header\",".
	                    "debugxml='',".
	                    "parentWindow=window.parent;".
	                    "parentWindow.showResult(debugresp,debugrespheader,debugxml);".
	                "</script>";
	    print($response);
	}

	//对发送的消息进行对称加密处理
	function encryptMsgBody($body){
		$token = $body["Token"];
		$encodingAesKey = $body["EncodingAESKey"];
		$corpId = $body["ToUserName"];
		
		$nonceStr = createNonceStr();
		$timeStamp = time();
		$wxcpt = new MsgCrypt($token, $encodingAesKey, $corpId);

		$encryptMsg = "";
		if(isset($body["EchoStr"])){  //如果是回调模式的验证情况			
			$EchoStr = $body["EchoStr"];					
		}else{			
			$EchoStr = $body["XML"];
		}
		$parser = new XMLParse();
		$wxcpt->EncryptMsg($EchoStr,$timeStamp,$nonceStr,$encryptMsg);
		
		return $parser->extractCallbackParamter($encryptMsg);
	}

	//获取接口的ID
	$tid = $_GET["tid"];

	if($tid){
		$config = getApiConfigById($tid);

		if($config){
			$method = $config->method;
			$url = $config->url;			

			//查询、删除类操作的处理逻辑
			if($method == "GET"){
				$body = array();
				foreach ($config->arguments as $key => $value) {
					if($value->name != "URL"){
						$tmp = isset($_GET[$value->name]) ? $_GET[$value->name] : "";

						if($tid == 36){  //如果是回调模式验证，缓存起来进行加密后再请求
							$body[$value->name] = $tmp;
						}else if($value->must){
							$url = appendParamter($url,$value->name,$tmp);
						}else if($tmp){
							$url = appendParamter($url,$value->name,$tmp);
						}
					}else{
						$url = $_GET[$value->name];
					}					
				}

				//回调模式对URL单独处理
				if($tid == 36){
					$params = encryptMsgBody($body);					
					$url = appendParamter(appendParamter($url,"echostr",urlencode($params[0])),"msg_signature",urlencode($params[1]));					
					$url = appendParamter(appendParamter($url,"timestamp",urlencode($params[2])),"nonce",urlencode($params[3]));
				}

				//如果是文件的下载接口则对返回的http头进行处理
				if($tid == 19){   
					$result = http_get($url);
					$header = explode("<br/>",$result["header"]);

					header($header[2]); 
				 	header($header[3]);
				 	header($header[4]);		
				 					 	
			 		echo $result["content"];				
				}else{
					$result = http_get($url);
					print(json_encode($result));
				}
			}else if($method == "POST"){  //更新、新增等操作则执行此处的逻辑				
				//如果是媒体文件上传类型则进行特殊处理
				if(isset($_FILES["media"])){
					foreach ($config->arguments as $key => $value) {
						$tmp = isset($_GET[$value->name]) ? $_GET[$value->name] : "";

						if($value->must){
							$url = appendParamter($url,$value->name,$tmp);
						}else if($tmp){
							$url = appendParamter($url,$value->name,$tmp);
						}
					}

					$info = array();
					$info["media"] = $_FILES["media"];

					printCrossDomainRes(http_post($url,$info,true));										
				}else{
					//对于只把body作为POST请求的情况则执行此处的逻辑
					if(isset($_GET["body"])){
						$body = json_decode(urldecode($_GET["body"]));

						foreach ($config->arguments as $key => $value) {
							if($value->name != "URL"){
								if($value->name != 'body'){
									$tmp = isset($_GET[$value->name]) ? $_GET[$value->name] : "";

									if($value->must){
										$url = appendParamter($url,$value->name,$tmp);
									}else if($tmp){
										$url = appendParamter($url,$value->name,$tmp);
									}
								}							
							}else{
								$url = $_GET[$value->name];
							}					
						}		
						
						$result = http_post($url,$body);
						print(json_encode($result));									
					}else{
						$body = array();						
						//没有body的POST情况需要做数据的加密处理
						foreach ($config->arguments as $key => $value) {
							if($value->name != "URL"){
								$body[$value->name] = urldecode($_GET[$value->name]);						
							}else{
								$url = $_GET[$value->name];
							}					
						}					

						$params = encryptMsgBody($body);
						$url = appendParamter($url,"msg_signature",urlencode($params[1]));
						$url = appendParamter($url,"timestamp",urlencode($params[2]));
						$url = appendParamter($url,"nonce",urlencode($params[3]));

						$parser = new XMLParse();
						$result = http_post($url,$parser->generateCallbackXml($params[0],$body["AgentID"],$body["ToUserName"]));
						print(json_encode($result));																		
					}																
				}				
			}			
		}else{
			print('{"errorCode":-1,"errorMsg":"config not find"}');
		}

	}else{
		print('{"errorCode":-2,"errorMsg":"missing interface id"}');
	}
	
?>

