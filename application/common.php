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

use think\Db;
use app\api\controller\Lib;

error_reporting(E_ERROR | E_WARNING | E_PARSE);
// 应用公共文件

//获取无限下级用户ID
function GetTeamMember($members, $mid)
{
    $Teams = array();//最终结果
    $mids = array($mid);//第一次执行时候的用户id
    do {
        $othermids = array();
        $state = false;
        foreach ($mids as $valueone) {
            foreach ($members as $key => $valuetwo) {
                if ($valuetwo['parent_id'] == $valueone) {
                    $Teams[] = $valuetwo['id'];//找到我的下级立即添加到最终结果中
                    $othermids[] = $valuetwo['id'];//将我的下级id保存起来用来下轮循环他的下级
                    unset($members[$key]);//从所有会员中删除他
                    $state = true;
                }
            }
        }
        $mids = $othermids;//foreach中找到的我的下级集合,用来下次循环
    } while ($state == true);

    return $Teams;
}

//用户数据加密       加密数据  
function jiami($data, $num, $numb)
{
    $length = mb_strlen($data, 'utf8') - $num - $numb;
    if ($length > 20) {
        $str = str_repeat("*", $length - 20);//替换字符数量
    } else {
        $str = $data;
    }

    $re = substr_replace($data, $str, $num, $length);
    return $re;
}

/**
 * 当日时间
 * @return false|int
 */
function day_time()
{
    return strtotime(date('Ymd'));
}

/*
 * 统一返回json格式
 * */
function rtn($code = 0, $msg = '', $data = array())
{
    return json_encode(array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ));
}

/**
 * toArray 输出
 * @param $param
 */
function print_toarray($param)
{
    if (!is_array($param)) {
        print_r($param->toArray());
    } else {
        print_r($param);
    }
}


/**
 *  打开sql监听
 */
function listen_sql()
{
    Db::listen(
        function ($sql, $time, $explain) {
// 记录SQL
            echo $sql;
//执行时间
            echo ' [' . $time . 's]';

            echo "<br />";
// 查看性能分析结果
//            dump($explain);
        }
    );
}

function stop()
{
    throw new \think\Exception("停止");
}


/**
 *自动生成秘钥
 */
function get_hash()
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+-';
    $random = $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)];//Random 5 times
    $content = uniqid() . $random;  // 类似 5443e09c27bf4aB4uT
    return sha1($content);
}

/**
 * 创建TOKEN
 * @return string
 */
function createToken()
{
    $code = chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE)) .
        chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE)) .
        chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE));
    $token = authCode($code);
    session('token', $token);
    return $token;
}

/**
 * 加密TOKEN
 * @param $str
 * @return string
 */
function authCode($str)
{
    $key = "andiamon";
    $str = substr(md5($str), 8, 10);
    return md5($key . $str);
}

/**
 * 生成助记词
 */

function memorizing_words()
{
    $memorizing_words = '';
    for ($i = 0; $i < 4; $i++) {
        $memorizing_words .= memorizing_words_active();
    }
    return $memorizing_words;
}

function memorizing_words_active()
{
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0, 25)]
        . strtoupper(dechex(date('m')))
        . date('d') . substr(time(), -5)
        . substr(microtime(), 2, 5)
        . sprintf('%02d', rand(0, 99));
    for (
        $a = md5($rand, true),
        $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
        $d = '',
        $f = 0;
        $f < 5;
        $g = ord($a[$f]),
        $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
        $f++
    ) ;
    return $d;
}

/**
 * 生成邀请码
 */
function make_coupon_card()
{
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0, 25)]
        . strtoupper(dechex(date('m')))
        . date('d') . substr(time(), -5)
        . substr(microtime(), 2, 5)
        . sprintf('%02d', rand(0, 99));
    for (
        $a = md5($rand, true),
        $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
        $d = '',
        $f = 0;
        $f < 8;
        $g = ord($a[$f]),
        $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
        $f++
    ) ;
    return $d;
}

/**
 * 原样输出print_r的内容
 * @param string $content 待print_r的内容
 */
function pre($content)
{
    echo "<pre>";
    print_r($content);
    echo "</pre>";
}

function pret($content)
{
    echo "<pre>";
    print_r($content);
    echo "</pre>";
    exit;
}

function validation_filter_id_card($id_card)
{
    if (strlen($id_card) == 18) {
        return idcard_checksum18($id_card);
    } elseif ((strlen($id_card) == 15)) {
        $id_card = idcard_15to18($id_card);
        return idcard_checksum18($id_card);
    } else {
        return false;
    }
}

/**
 * 随机
 */
function generate_code($length = 4)
{
    return rand(pow(10, ($length - 1)), pow(10, $length) - 1);
}

/**
 * 加密密码
 * @param string $data 待加密字符串
 * @return string 返回加密后的字符串
 */
function encrypt($data)
{
    return md5(config('DATA_AUTH_KEY') . md5($data));
}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL, $format = 'Y-m-d H:i')
{
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 检测是否为手机号
 */
function is_mobile($str)
{
    $pattern = "/^(13|14|15|16|17|18|19)\d{9}$/";
    if (preg_match($pattern, $str)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 检测是否为邮箱
 */
function is_email($str)
{
    $pattern = '/^[a-z0-9][a-z\.0-9-_]+@[a-z0-9_-]+(?:\.[a-z]{0,3}\.[a-z]{0,2}|\.[a-z]{0,3}|\.[a-z]{0,2})$/i';
    if (preg_match($pattern, $str)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 检测验证密码
 */
function check_pwd($str)
{
    $pattern = '/^(?=.*?[a-zA-Z])(?=.*?[0-9])[a-zA-Z0-9]{8,20}$/';
    if (preg_match($pattern, $str)) {
        return true;
    } else {
        return false;
    }
}

/**
 * +----------------------------------------------------------
 * 功能：字符串截取指定长度
 * leo.li hengqin2008@qq.com
 * +----------------------------------------------------------
 * @param string $string 待截取的字符串
 * @param int $len 截取的长度
 * @param int $start 从第几个字符开始截取
 * @param boolean $suffix 是否在截取后的字符串后跟上省略号
 * +----------------------------------------------------------
 * @return string               返回截取后的字符串
 * +----------------------------------------------------------
 */
function cutStr($str, $len = 100, $start = 0, $suffix = 1)
{
    $str = strip_tags(trim(strip_tags($str)));
    $str = str_replace(array("\n", "\t", "/ /"), "", $str);
    if (strlen($str) < $len * 3) {//strlen统计字符长度 UFT-8占用三个字符
        return $str;
    } else {
        $strlen = mb_strlen($str);
        while ($strlen) {
            $array[] = mb_substr($str, 0, 1, "utf8");
            $str = mb_substr($str, 1, $strlen, "utf8");
            $strlen = mb_strlen($str);
        }
        $end = $len + $start;
        $str = '';

        for ($i = $start; $i < $end; $i++) {
            $str .= $array[$i];
        }
        return count($array) > $len ? ($suffix == 1 ? $str . "..." : $str) : $str;
    }
}

/**
 * 生成订单号
 */
function generateOrderNumber()
{
    return date('md') . substr(implode('', array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

/**
 * 功能：生成二维码
 * @param string $qrData 手机扫描后要跳转的网址
 * @param string $qrLevel 默认纠错比例 分为L、M、Q、H四个等级，H代表最高纠错能力
 * @param string $qrSize 二维码图大小，1－10可选，数字越大图片尺寸越大
 * @param string $savePath 图片存储路径
 * @param string $savePrefix 图片名称前缀
 */
function createQRcode($savePath, $qrData = 'PHP QR Code :)', $qrLevel = 'L', $qrSize = 4, $savePrefix = 'qrcode')
{
    if (!isset($savePath)) return '';
    //设置生成png图片的路径
    $PNG_TEMP_DIR = $savePath;

    //检测并创建生成文件夹
    if (!file_exists($PNG_TEMP_DIR)) {
        mkdir($PNG_TEMP_DIR);
    }
    $filename = $PNG_TEMP_DIR . 'test.png';
    $errorCorrectionLevel = 'L';
    if (isset($qrLevel) && in_array($qrLevel, ['L', 'M', 'Q', 'H'])) {
        $errorCorrectionLevel = $qrLevel;
    }
    $matrixPointSize = 4;
    if (isset($qrSize)) {
        $matrixPointSize = min(max((int)$qrSize, 1), 10);
    }
    if (isset($qrData)) {
        if (trim($qrData) == '') {
            die('data cannot be empty!');
        }
        //生成文件名 文件路径+图片名字前缀+md5(名称)+.png
        $filename = $PNG_TEMP_DIR . $savePrefix . md5($qrData . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
        //开始生成
        \PHPQRCode\QRcode::png($qrData, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    } else {
        //默认生成
        \PHPQRCode\QRcode::png('PHP QR Code :)', $filename, $errorCorrectionLevel, $matrixPointSize, 2);
    }
    if (file_exists($PNG_TEMP_DIR . basename($filename)))
        return basename($filename);
    else
        return FALSE;
}

//火币网或者中币网接口 $cur 币种名称
function api_currency($cur)
{
    $currency = strtolower($cur);//先转小写
    if ($currency == 'usdt') {
        $price = 1;
    } else if ($currency == 'doge') {
        $url = "http://api.zb.cn/data/v1/kline?market=doge_usdt&type=1min&size=1";
        $zb_data = json_decode(file_get_contents($url), true);
        $price = $zb_data['data'][0][4];
    } else {
        $Lib = new Lib();
        $huobi = $Lib->get_market_tickers();//全部symbol的交易行情
        $huobi_data = $huobi['data'];//取出信息数组
        foreach ($huobi_data as $k => $v) {//遍历
            $cur_area = $currency . 'usdt';//拼接usdt
            if ($v['symbol'] == $cur_area) {
                $price = $v['close'];//最新单价 = 最新收盘价
            }
        }
    }
    return $price;
}

/**
 * 取文件最后$n行
 * @param string $filename 文件路径
 * @param int $line 最后几行
 * @param $tag
 * @return mixed false表示有错误，成功则返回字符串
 */
function FileLastLines($filename, $line, $tag = "")
{
    $handle = fopen($filename, 'r+');//读写模式打开文件，指针指向文件起始位置
    $pos = -1;
    $eof = "";
    $str = "";
    $string = 1;
    while ($line > 0 && $string) {
        while ($eof != $tag) {
            if (!fseek($handle, $pos, SEEK_END)) {
                $eof = fgetc($handle);
                $pos--;
            } else {
                break;
            }
        }
        $string = fgets($handle);
        if ($string) {
            $str = $string;
        }
        $eof = "";
        $line--;
    }
    return $str;
}

function format_time($time, $type)
{
    if (in_array($type, array(1, 2, 3))) {
        $time = date('m.d', $time);
    } elseif (in_array($type, array(5, 6, 7, 8))) {
        $time = date('m.d H:i', $time);
    } else {
        $time = date('m.d H:i', $time);
    }
    return $time;
}

/**
 * 模拟post进行url请求
 * @param string $url
 * @param string $param
 */
function request_post($url = '', $param = '')
{
    if (empty($url) || empty($param)) {
        return false;
    }
    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL, $postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return $data;
}
