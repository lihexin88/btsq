<?php

/**
 * User: Young
 * Date: 2017/2/13
 * Time: 15:00
 */

namespace app\common\model;
use think\Model;
class Base extends Model
{

    public function _initialize()
    {
        parent::_initialize();
    }
	
	//获取星期方法
    public function   get_week($date){
        //强制转换日期格式
        $date_str=date('Y-m-d',strtotime($date));
    
        //封装成数组
        $arr=explode("-", $date_str);
         
        //参数赋值
        //年
        $year=$arr[0];
         
        //月，输出2位整型，不够2位右对齐
        $month=sprintf('%02d',$arr[1]);
         
        //日，输出2位整型，不够2位右对齐
        $day=sprintf('%02d',$arr[2]);
         
        //时分秒默认赋值为0；
        $hour = $minute = $second = 0;   
         
        //转换成时间戳
        $strap = mktime($hour,$minute,$second,$month,$day,$year);
         
        //获取数字型星期几
        $number_wk=date("w",$strap);
         
        //自定义星期数组
        $weekArr=array(7,1,2,3,4,5,6);
         
        //获取数字对应的星期
        return $weekArr[$number_wk];
    }

}