<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function get_config()
{
    return $config = [
        //官网地址
        'base_url' => "http://www.711688.net.cn/",
        'database'=>[
            // 数据库连接地址
            'hostname' => "127.0.0.1",
            // 数据库名称
            'dbname'   => "",
            // 数据库账户
            'username' => "",
            // 数据库密码
            'password' => "",
            // 数据库表前缀
            'prefix'     => "cj_",
            'resultset_type' => false,
        ],
        'auth'=>[
            0=>[
                'url'=>'https://yikai.cnshangji.cn/',
                'name'=>'111111',
                'pass'=>'222222',
            ]
        ]
    ];
}
