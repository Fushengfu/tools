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
		<a href="#微信">微信</a>
		<ul>
			<li> <a href="#微信接口使用">微信接口使用</a></li>
			<li> <a href="#接收微信消息">接收微信消息</a></li>
		</ul>
	</li>
</ul>

# 微信

## 微信接口使用

```
use Amulet\Wechat\Wechat;

#$options = [
	'token'=> '',
	'encodingAesKey'=> '',
	'appid'=> '',
	'appsecret'=> '',
	....
];

#实例化
$wechat = new Wechat((array)$options);
#验证是否是微信服务器请求
$wechat->checkSignature($query);
```

## 接收微信消息
```
use Amulet\Wechat\{Wechat, Receive};

#$options = [
	'token'=> '',
	'encodingAesKey'=> '',
	'appid'=> '',
	'appsecret'=> '',
	....
];

#实例化
$wechat = new Wechat((array)$options);
#验证是否是微信服务器请求
$wechat->checkSignature($query);

#接收微信事件推送消息 实例化
// 实例化消息对象
$recv = Receive::instance((array)$options);
```