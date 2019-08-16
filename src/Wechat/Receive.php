<?php

namespace Amulet\Wechat;

// +----------------------------------------------------------------------
// | Date 03-21
// +----------------------------------------------------------------------
// | Author: fushengfu <shengfu8161980541@qq.com>
// +----------------------------------------------------------------------

class Receive {
    /** 消息推送地址 */
    const CUSTOM_SEND_URL           = '/message/custom/send?';
    const MASS_SEND_URL             = '/message/mass/send?';
    const TEMPLATE_SET_INDUSTRY_URL = '/message/template/api_set_industry?';
    const TEMPLATE_ADD_TPL_URL      = '/message/template/api_add_template?';
    const TEMPLATE_SEND_URL         = '/message/template/send?';
    const MASS_SEND_GROUP_URL       = '/message/mass/sendall?';
    const MASS_DELETE_URL           = '/message/mass/delete?';
    const MASS_PREVIEW_URL          = '/message/mass/preview?';
    const MASS_QUERY_URL            = '/message/mass/get?';

    /** 消息回复类型 */
    const MSGTYPE_TEXT      = 'text';
    const MSGTYPE_IMAGE     = 'image';
    const MSGTYPE_LOCATION  = 'location';
    const MSGTYPE_LINK      = 'link';
    const MSGTYPE_EVENT     = 'event';
    const MSGTYPE_MUSIC     = 'music';
    const MSGTYPE_NEWS      = 'news';
    const MSGTYPE_VOICE     = 'voice';
    const MSGTYPE_VIDEO     = 'video';

    // 接受请求数据
    private $_receive;

    // 类的实例
    private static $instance;

    private function __construct(){
        $postStr = file_get_contents("php://input");
        if (stripos('<xml>', $postStr)) {
            $this->_receive = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        } else {
            $this->_receive = json_decode($postStr, true);
        }
    }

    // 解密数据
    protected function decryptMsg($encryptMsg){
        $timeStamp  = $_GET['timeStamp'];
        $nonce      = $_GET['nonce'];

        $xml_tree   = new DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e    = $xml_tree->getElementsByTagName('Encrypt');
        $array_s    = $xml_tree->getElementsByTagName('MsgSignature');
        $encrypt    = $array_e->item(0)->nodeValue;
        // $msg_sign   = $array_s->item(0)->nodeValue;
        $msg_sign   = $_GET['msg_signature'];

        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);

        // 第三方收到公众号平台发送的消息
        $msg = '';
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if ($errCode == 0) {
            print("解密后: " . $msg . "\n");
        } else {
            print($errCode . "\n");
        }
    }

    // 创建操作对象
    public static function instance(){
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    // 获取微信服务器发来的信息
    public function getMsg(){
        return $this->_receive;
    }

    // 获取消息发送人的openid
    public function getFromUserName(){
        return $this->_receive['FromUserName'];
    }

    // 获取消息id
    public function getMsgId(){
        return $this->_receive['MsgId'];
    }

    // 获取媒体消息id
    public function getMediaId(){
        return $this->_receive['MediaId'];
    }

    // 获取图片链接
    public function getPicUrl(){
        return $this->_receive['PicUrl'];
    }

    // 获取公众号id
    public function getToUserName(){
        return $this->_receive['ToUserName'];
    }

    // 获取消息成功接收时间
    public function getCreateTime(){
        return $this->_receive['CreateTime'];
    }

    // 消息类型
    public function getMsgType(){
        return $this->_receive['MsgType'];
    }

    // 获取事件类型
    public function getEvent(){
        return $this->_receive['Event'];
    }

    // 消息内容
    public function getContent(){
        return $this->_receive['Content'];
    }

    // 语音识别结果
    public function getRecognition(){
        return $this->_receive['Recognition'];
    }

    // 获取微信推送位置经纬度和高精度
    public function getLocation(){
        $location = [
            'latitude'  => $this->_receive['Latitude'],
            'longitude' => $this->_receive['Longitude'],
            'precision' => $this->_receive['Precision']
        ];
        return $location;
    }

    // 获取主动发送位置
    public function getLabel(){
        return $this->_receive['Label'];
    }

    // 获取主动发送经纬度位置
    public function getActiveLocation(){
        $location = [
            'location_x'  => $this->_receive['Location_X'],
            'location_y'  => $this->_receive['Location_Y']
        ];
        return $location;
    }

    public function getThumbMediaId(){
        return $this->_receive['ThumbMediaId'];
    }

}
