<?php
// 常量定义
const WEB_VERSION = '1.0.170303';
/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_login()
{
    $aid = session('aid');
    if (empty($aid) or $aid < 1) {
        return 0;
    } else {
        return $aid;
    }
}

/**
 * 检测验证码
 * @param  integer $id 验证码ID
 * @return boolean     检测结果
 */
function check_verify($code, $id = 1)
{
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 功能：计算文件大小
 * @param int $bytes
 * @return string 转换后的字符串
 */
function byteFormat($bytes)
{
    $sizetext = array(" B", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2) . $sizetext[$i];
}

/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function is_administrator($aid = null)
{
    $aid = is_null($aid) ? is_login() : $aid;
    return $aid && (intval($aid) === config('USER_ADMINISTRATOR'));
}

/**
 * 判断用户是否属于特定用户组
 * @return boolean true-属于特定组，false-不属于
 * @author leo.lei <lgb531@126.com>
 */
function is_group($group)
{
    if (is_administrator()) {
        return false;
    } else {
        $group_id = M('AuthGroupAccess')->where(array('uid' => session('aid')))->getField('group_id');
        if ($group_id == $group) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 获取当前页面URL
 * @return string 返回url
 */
function this_action()
{
    parse_str($_SERVER['QUERY_STRING'], $arr);
    unset($arr['p']);
    unset($arr['s']);
    if ($arr) {
        return http_build_query($arr) . '&';
    } else {
        return false;
    }
}

/**
 * boot分页函数
 * @param string $count , $item, $list, $p ,$action 总页数， 一页多少个， 显示多少个分页, 当前页数
 * @return string 返回分页字符串
 */
function boot_page($count, $item, $list, $p,$action)
{// 最大页数
    $max = ceil($count / $item);
    if ($max <= 1) {
        $page = "";
    } else {// 首页
        $page = '<li class="paginate_button"><a href="' . $action . '?' . this_action() . '&p=1">«</a></li>';
        // 显示的第一个
        $start = $p - floor($list / 2);
        if ($start <= 0) {
            $start = 1;
        }
        // 显示的最后一个
        $stop = $p + floor($list / 2);
        if ($stop > $max) {
            $stop = $max;
        }
        for ($i = $start; $i <= $stop; $i++) {
            if ($i == $p) {
                // 选中当前页
                $page .= '<li class="paginate_button active"><a>' . $i . '</a></li>';
            } else {
                $page .= '<li class="paginate_button"><a href="' . $action . '?' . this_action() . 'p=' . $i . '">' . $i . '</a></li>';
            }
        }
        // 末页
        $page .= '<li class="paginate_button"><a href="' . $action . '?' . this_action() . 'p=' . $max . '">»</a></li>';
    }
    return $page;
}

/**
 * 获取字典列表
 * @param  string $str 字段类型
 * @return array       返回数组
 */
function dict_list($str)
{
    return model("Common/Dict")->showList($str);
}