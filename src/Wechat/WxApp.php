<?php

namespace Amulet\Wechat;

// +----------------------------------------------------------------------
// | Date 03-21
// +----------------------------------------------------------------------
// | Author: fushengfu <shengfu8161980541@qq.com>
// +----------------------------------------------------------------------

use Amulet\Wechat\ErrCode;
class WxApp
{
	const BASE_URL = 'https://api.weixin.qq.com/';
	const JSCODE_2_SESSION = 'sns/jscode2session?';

	private $token;
	private $encodingAesKey;
	private $encrypt_type;
	public $appid;
	public $mapkey;
	public $appsecret;
	public $access_token;
	private $jsapi_ticket;
	private $api_ticket;
	public $user_token;
	private $partnerid;
	private $partnerkey;
	private $paysignkey;
	private $postxml;
	private $_msg;
	private $_funcflag = false;
	private $_receive;
	private $_text_filter = true;
	public $debug =  false;
	public $errCode = 40001;
	public $errMsg = "no access";
	public $logcallback;

	public function __construct($options)
	{
		$this->token = isset($options['token'])?$options['token']:'';
		$this->encodingAesKey = isset($options['encodingaeskey'])?$options['encodingaeskey']:'';
		$this->appid = isset($options['appid'])?$options['appid']:'';
		$this->mapkey = isset($options['mapkey'])?$options['mapkey']:'';
		$this->appsecret = isset($options['appsecret'])?$options['appsecret']:'';
		$this->debug = isset($options['debug'])?$options['debug']:false;
		$this->logcallback = isset($options['logcallback'])?$options['logcallback']:false;
	}

	/**
	 * 验证消息是来自微信发送的
	 */
	public function checkSignature($get = array())
	{
		$signature = isset($get["signature"])?$get["signature"]:'';
		$signature = isset($get["msg_signature"])?$get["msg_signature"]:$signature; //如果存在加密验证则用加密验证段
		$timestamp = isset($get["timestamp"])?$get["timestamp"]:'';
		$nonce = isset($get["nonce"])?$get["nonce"]:'';
		$token = $this->token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		p($signature);
		p($tmpStr);
		if($tmpStr == $signature){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 微信接口消息验证
	 * @param bool $return 是否返回
	 */
	public function valid($return=false)
    {
        $encryptStr="";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $postStr = file_get_contents("php://input");
            $array = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->encrypt_type = isset($_GET["encrypt_type"]) ? $_GET["encrypt_type"]: '';
            if ($this->encrypt_type == 'aes') { //aes加密
                $this->log($postStr);
            	$encryptStr = $array['Encrypt'];
            	$pc = new Prpcrypt($this->encodingAesKey);
            	$array = $pc->decrypt($encryptStr,$this->appid);
            	if (!isset($array[0]) || ($array[0] != 0)) {
            	    if (!$return) {
            	        die('decrypt error!');
            	    } else {
            	        return false;
            	    }
            	}
            	$this->postxml = $array[1];
            	if (!$this->appid)
            	    $this->appid = $array[2];//为了没有appid的订阅号。
            } else {
                $this->postxml = $postStr;
            }
        } elseif (isset($_GET["echostr"])) {
        	$echoStr = $_GET["echostr"];
        	if ($return) {
        		if ($this->checkSignature()){
        			return $echoStr;
        		}else{
        			return false;
        		}
        	} else {
        		if ($this->checkSignature())
        			die($echoStr);
        		else
        			die('no access');
        	}
        }

        if (!$this->checkSignature($encryptStr)) {
        	if ($return)
        		return false;
        	else
        		die('no access');
        }
        return true;
    }

   /**
	 * 获取自定义菜单的扫码推事件信息
	 * @return: array | false
	 */
   public function code2Session($code)
   {
   		$url = BASE_URL.JSCODE_2_SESSION.'appid='.$this->appid.'&secret='.$this->appsecret.'&js_code='.$code.'&grant_type=authorization_code';
   		return http_get($url);
   }
}
