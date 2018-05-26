<?php

return array (
  0 => 
  array (
    'name' => 'sitename',
    'title' => '站点名称',
    'type' => 'string',
    'content' => 
    array (
    ),
    'value' => '我的CMS网站',
    'rule' => 'required',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  1 => 
  array (
    'name' => 'theme',
    'title' => '皮肤',
    'type' => 'string',
    'content' => 
    array (
    ),
    'value' => 'default',
    'rule' => 'required',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  2 => 
  array (
    'name' => 'qrcode',
    'title' => '公众号二维码',
    'type' => 'image',
    'content' => 
    array (
    ),
    'value' => '/assets/addons/cms/img/qrcode.png',
    'rule' => '',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  3 => 
  array (
    'name' => 'default_archives_img',
    'title' => '文档默认图片',
    'type' => 'image',
    'content' => 
    array (
    ),
    'value' => '/assets/addons/cms/img/noimage.jpg',
    'rule' => '',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  4 => 
  array (
    'name' => 'default_channel_img',
    'title' => '栏目默认图片',
    'type' => 'image',
    'content' => 
    array (
    ),
    'value' => '/assets/addons/cms/img/noimage.jpg',
    'rule' => '',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  5 => 
  array (
    'name' => 'default_block_img',
    'title' => '区块默认图片',
    'type' => 'image',
    'content' => 
    array (
    ),
    'value' => '/assets/addons/cms/img/noimage.jpg',
    'rule' => '',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  6 => 
  array (
    'name' => 'default_page_img',
    'title' => '单页默认图片',
    'type' => 'image',
    'content' => 
    array (
    ),
    'value' => '/assets/addons/cms/img/noimage.jpg',
    'rule' => '',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  7 => 
  array (
    'name' => 'domain',
    'title' => '绑定二级域名前缀',
    'type' => 'string',
    'content' => 
    array (
    ),
    'value' => '',
    'rule' => '',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  8 => 
  array (
    'name' => 'rewrite',
    'title' => '伪静态',
    'type' => 'array',
    'content' => 
    array (
    ),
    'value' => 
    array (
      'index/index' => '/cms$',
      'channel/index' => '/cms/c/[:diyname]',
      'tags/index' => '/cms/t/[:name]',
      'archives/index' => '/cms/a/[:diyname]',
      'page/index' => '/cms/p/[:diyname]',
      'search/index' => '/cms/s',
      'wxapp.index/index' => '/cms/wxapp',
    ),
    'rule' => 'required',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  9 => 
  array (
    'name' => 'wxappid',
    'title' => '小程序AppID',
    'type' => 'string',
    'content' => 
    array (
    ),
    'value' => 'appid',
    'rule' => 'required',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
  10 => 
  array (
    'name' => 'wxappsecret',
    'title' => '小程序AppSecret',
    'type' => 'string',
    'content' => 
    array (
    ),
    'value' => 'appsecret',
    'rule' => 'required',
    'msg' => '',
    'tip' => '',
    'ok' => '',
    'extend' => '',
  ),
);
