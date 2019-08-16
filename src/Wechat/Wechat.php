<?php

namespace Amulet\Wechat;

// +----------------------------------------------------------------------
// | Date 03-21
// +----------------------------------------------------------------------
// | Author: fushengfu <shengfu8161980541@qq.com>
// +----------------------------------------------------------------------

use Amulet\Wechat\ErrCode;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use App\Model\WechatFans;

class Wechat
{

	// https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type=jsapi
	// https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=ACCESS_TOKEN
	// https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=APPID&secret=APPSECRET
	const API_URL_PREFIX = 'https://api.weixin.qq.com';
	const AUTH_URL = '/cgi-bin/token?grant_type=client_credential&';
	const GET_USER_INFO = "/sns/userinfo?access_token=";
	const WEB_AUTHPREFIX = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
	const GET_ACCESS_TOKEN = '/sns/oauth2/access_token?';
	const GET_REFLESH_TOKEN = '/sns/oauth2/refresh_token?';
	const GET_TICKET = '/cgi-bin/ticket/getticket?';
	const JSCODE_2_SESSION = '/sns/jscode2session?';
	const GET_WXACODE = '/wxa/getwxacodeunlimit?';

	private $token;//
	private $encodingAesKey;//
	public $appid;//
	public $mapkey;//
	public $appsecret;//
	public $debug =  false;//
	public $logcallback;//
	public $access_token;
	private $refresh_token;
	private $expires_in;

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
	public $errCode = 40001;
	public $errMsg = "no access";

	public function __construct($options)
	{
		$this->token 	   = $options['token']??'';
		$this->encodingAesKey = $options['encodingaeskey']??'';
		$this->appid 	   = $options['appid']??'';
		$this->mapkey 	   = $options['mapkey']??'';
		$this->appsecret   = $options['appsecret']??'';
		$this->debug 	   = $options['debug']??false;
		$this->logcallback = $options['logcallback']??false;
		$this->refresh_token = $options['refresh_token']??null;
		$this->access_token = $options['access_token']??'';
		$this->expires_in = $options['expires_in']??0;
	}

	/**
	 * 验证消息是来自微信发送的
	 */
	public function checkSignature($get = array())
	{
		$signature = $get["signature"]??'';
		$signature = $get["msg_signature"]??$signature; //如果存在加密验证则用加密验证段
		$timestamp = $get["timestamp"]??'';
		$nonce 	   = $get["nonce"]??'';
		$token     = $this->token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		if ($tmpStr == $signature){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 通过code获取用户唯一标识 OpenID 和 会话密钥 session_key。
	 * @return: array | false
	 */
   public function code2Session($code)
   {
   		$url = self::API_URL_PREFIX.self::JSCODE_2_SESSION.'appid='.$this->appid.'&secret='.$this->appsecret.'&js_code='.$code.'&grant_type=authorization_code';
   		return $this->httpGet($url);
   }

   public function getWxApp2Code($openid)
   {
	   	if (!$access_token = $this->getCache('wx_app_2code_access_token')) {

	   		$result = json_decode($this->httpGet(self::API_URL_PREFIX.self::AUTH_URL.'appid='.$this->appid.'&secret='.$this->appsecret), true);

	   		if (!isset($result['errcode'])) {
	   			$access_token =  $result['access_token'];
		   		$this->setCache('wx_app_2code_access_token', $result['access_token'], $result['expires_in']);
	   		}
	   	}
	   	$uri = self::API_URL_PREFIX.self::GET_WXACODE.'access_token='.$access_token;
	   	$result = $this->httpPost($uri, json_encode(['scene'=> $openid]));

	   	$res = json_decode($result, true);
	   	if( isset($res['errcode']) && $res['errcode'] == 42001 ){
	   		$result = json_decode($this->httpGet(self::API_URL_PREFIX.self::AUTH_URL.'appid='.$this->appid.'&secret='.$this->appsecret), true);

	   		if (!isset($result['errcode'])) {
	   			$access_token =  $result['access_token'];
		   		$this->setCache('wx_app_2code_access_token', $result['access_token'], $result['expires_in']);
	   		}
	   		$uri = self::API_URL_PREFIX.self::GET_WXACODE.'access_token='.$access_token;
	   		$result = $this->httpPost($uri, json_encode(['scene'=> $openid]));
	   		$res = json_decode($result, true);
	   	}
	   	p($access_token);
	   	if (isset($res['errcode'])) {
	   		return false;
	   	}

	   	$url = UPLOADS_PATH.$openid.'.jpg';
	   	// 写入图片信息
	   	p($result, true,$url);
	   	return request()->getSchemeAndHttpHost().'/public/uploads/'.$openid.'.jpg';
   }

	public function oauth()
	{
		if ($_GET['code']??false || ($this->expires_in != 0 && time() - $this->expires_in < 30 * 24 * 60 * 60 + 7200)) {
			if (!$result = $this->getOauthAccessToken($this->refresh_token)) {
				return ['OAUTH_ERROR'=>$this->weChat->errCode,'data'=>$this->weChat->errMsg];
      		}

			$this->fansInfo = $result;
			if(!$fansInfo = $this->getUserInfo($this->access_token = $result['access_token'],$this->openid = $result['openid'], $lang = 'zh_CN')){
				return ['OAUTH_ERROR'=>$this->weChat->errCode,'data'=>'获取微信用户信息'];
			}

	    	if ($fansModel = WechatFans::where('openid',$this->openid)->first()) {
	    		$fansModel->access_token  = $this->access_token;
	    		$fansModel->refresh_token = $this->refresh_token;
	    		$fansModel->expires_in    = $this->expires_in + time();
	    		$bool = $fansModel->save();
	    	} else {
	    		$fansInfo['access_token'] = $this->access_token;
	    		$fansInfo['refresh_token']= $this->refresh_token;
	    		$fansInfo['expires_in']   = $this->expires_in + time();
	    		$fansModel = new WechatFans;
	    		$bool = $fansModel->save($fansInfo);
	    	}
	    	return $bool;

		} else {
			$url = self::WEB_AUTHPREFIX.'appid='.$this->appid.'&redirect_uri='.urlencode(env('APP_URL')).'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
			header("location:".$url);
		}
	}

	/**
	 * 通过code获取Access Token
	 * @return array {access_token,expires_in,refresh_token,openid,scope}
	 */
	protected function getOauthAccessToken($refresh_token)
	{
		if ( $_GET['code']??false) {
			$url = self::API_URL_PREFIX.self::GET_ACCESS_TOKEN.'appid='.$this->appid.'&secret='.$this->appsecret.'&code='.$code.'&grant_type=authorization_code';
		} else {
			$url = self::API_URL_PREFIX.self::GET_REFLESH_TOKEN.'appid='.$this->appid.'&refresh_token='.$this->refresh_token.'&grant_type=refresh_token';
		}

		$result = $this->httpGet($url);
		if ($result){
			$json = json_decode($result,true);
			if ( !$json || !empty($json['errcode']) ) {
				$this->errCode = $json['errcode'];
				$this->errMsg  = $json['errmsg'];
				$errMsgInfo    = ErrCode::getErrText($json['errcode']);
				p($errMsgInfo,false,CACHE_PATH.'wechat_error'.date('Ymd').'.txt');
				return false;
			}
			return $json;
		}
		return false;
	}

  /**
	 * 获取关注者基本信息
	 * @param string $openid
	 * @param string $lang 返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
	 * @return array {subscribe,openid,nickname,sex,city,province,country,language,headimgurl,subscribe_time,[unionid]}
	 * 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。建议调用前用isset()检测一下
	 */
	protected function getUserInfo($access_token,$openid, $lang = 'zh_CN'){
		if (!$access_token) return false;
		$url = self::API_URL_PREFIX.self::GET_USER_INFO.$access_token."&openid=".$openid."&lang=zh_CN";
		$result = $this->httpGet($url);

		if ($result) {
			$json = json_decode($result,true);
			if (isset($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg  = $json['errmsg'];
				$errMsgInfo    = ErrCode::getErrText($json['errcode']);
				p($errMsgInfo,false,CACHE_PATH.'wechat_error'.date('Ymd').'.txt');
				return false;
			}
			return $json;
		}
		return false;
	}

	/**
	 * 获取access_token
	 * @param string $appid 如在类初始化时已提供，则可为空
	 * @param string $appsecret 如在类初始化时已提供，则可为空
	 * @param string $token 手动指定access_token，非必要情况不建议用
	 */
	public function checkAuth($appid = '', $appsecret = '', $token = '')
	{
		if (!$appid || !$appsecret) {
			$appid = $this->appid;
			$appsecret = $this->appsecret;
		}
		if ($token) { //手动指定token，优先使用
		    $this->access_token = $token;
		    return $this->access_token;
		}

		$authname = 'wechat_access_token'.$appid;
		if ($rs = $this->getCache($authname)) {
			$this->access_token = $rs;
			return $rs;
		}

		$result = $this->httpGet(self::API_URL_PREFIX.self::AUTH_URL.'appid='.$appid.'&secret='.$appsecret);
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || isset($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			$this->access_token = $json['access_token'];
			$expire = $json['expires_in']? intval($json['expires_in']) : 7200;
			$this->setCache($authname,$this->access_token,$expire);
			return $this->access_token;
		}
		return false;
	}

	public function getTicket()
	{
		$authname = 'wechat_ticket'.$this->appid;;
		if ($rs = $this->getCache($authname)) {
			$this->ticket = $rs;
			return $rs;
		}

		$result = $this->httpGet(self::API_URL_PREFIX.self::GET_TICKET.'access_token='.$this->access_token.'&type=jsapi');
		if ($result)
		{
			$json = json_decode($result,true);
			if ( !$json || $json['errcode'] != 0 ) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			$this->ticket = $json['ticket'];
			$expire = $json['expires_in']? intval($json['expires_in']) : 7200;
			$this->setCache($authname,$this->ticket,$expire);
			return $this->ticket;
		}
		return false;
	}

	public function jsapiTicket($debug = false)
	{
		$this->checkAuth();
        $this->getTicket();
		$noncestr = createToken(16);
		$timestamp = time();

		$string = 'jsapi_ticket='.$this->ticket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.request()->header('referer');
		$signature = [
            'debug' 	=> $debug,
            'appId' 	=> $this->appid,
            'timestamp' => $timestamp,
            'nonceStr'  => $noncestr,
            'signature' => sha1($string)
        ];
		return $signature;
	}

	/**
	 * 批量获取关注用户列表
	 * @param unknown $next_openid
	 */
	public function getUserList($next_openid=''){
		if (!$this->access_token && !$this->checkAuth()) return false;
		$result = $this->httpGet(self::API_URL_PREFIX.self::USER_GET_URL.'access_token='.$this->access_token.'&next_openid='.$next_openid);
		if ($result)
		{
			$json = json_decode($result,true);
			if (isset($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}

	/**
	 * 批量获取关注者详细信息
	 * @param array $openids user_list{{'openid:xxxxxx'},{},{}}
	 * @return array user_info_list{subscribe,openid,nickname,sex,city,province,country,language,headimgurl,subscribe_time,[unionid]}{}{}...
	 * 注意：unionid字段 只有在用户将公众号绑定到微信开放平台账号后，才会出现。建议调用前用isset()检测一下
	 */
	public function getUsersInfo($openids){
		if (!$this->access_token && !$this->checkAuth()) return false;
		$result = $this->httpPost(self::API_URL_PREFIX.self::USERS_INFO_URL.'access_token='.$this->access_token,json_encode($openids));
		if ($result)
		{
			$json = json_decode($result,true);
			if (isset($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}

	/**
	 * GET 请求
	 * @param string $url
	 */
	private function httpGet($url)
	{
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

	/**
	 * POST 请求
	 * @param string $url
	 * @param array $param
	 * @param boolean $post_file 是否文件上传
	 * @return string content
	 */
	private function httpPost($url,$param,$post_file=false)
	{
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		if (PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')) {
			$is_curlFile = true;
		} else {
			$is_curlFile = false;
			if (defined('CURLOPT_SAFE_UPLOAD')) {
				curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
			}
		}
		if (is_string($param)) {
			$strPOST = $param;
		}elseif($post_file) {
			if($is_curlFile) {
				foreach ($param as $key => $val) {
					if (substr($val, 0, 1) == '@') {
						$param[$key] = new \CURLFile(realpath(substr($val,1)));
					}
				}
			}
			$strPOST = $param;
		} else {
			$aPOST = array();
			foreach($param as $key=>$val){
				$aPOST[] = $key."=".urlencode($val);
			}
			$strPOST =  join("&", $aPOST);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($oCurl, CURLOPT_POST,true);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}	else {
			return false;
		}
	}


	/**
	 * 设置缓存
	 * @param string $cachename
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function setCache($cachename,$value,$expire = 0){
		//TODO: set cache implementation
		$expire == 0? 360 * 24 * 3600:$expire;
		return Cache::put($cachename, $value, $expire);
		try {
			$expire >0 && Redis::expire($cachename, $expire);
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * 获取缓存
	 * @param string $cachename
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function getCache($cachename){
		//TODO: set cache implementation
		return Cache::get($cachename,null);
	}

	/**
	 * 清除缓存，按需重载
	 * @param string $cachename
	 * @return boolean
	 */
	protected function removeCache($cachename){
		//TODO: remove cache implementation
		Cache::forget($cachename);
		if (Cache::has($cachename)) {
			return true;
		}
		return false;
	}

}
