# tools
工具包

# 简介

内置集成微信开发工具包.

### 目录说明

```
src
│
├─Http                 Http请求类 目录
│
├─Wechat               微信工具包 目录


```
### Installaction

```
composer require amulet/amulet-tools
```

# Contents
<ul>
	<li>
		<a href="#微信接口使用">1、微信接口使用</a>
	</li>
</ul>

## 微信接口使用

1、微信接口使用
```
//引入包名
use Amulet\Wechat\{Wechat, Receive};
#
#$options = [
	'token'=> '',
	'encodingAesKey'=> '',
	'appid'=> '',
	'appsecret'=> '',
	....
];
//
#实例化
$wechat = new Wechat((array)$options);
#验证是否是微信服务器请求
$wechat->checkSignature($query);
##
#接收微信事件推送消息 实例化
// 实例化消息对象
$recv = Receive::instance((array)$options);
```