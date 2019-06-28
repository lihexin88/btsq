<?php

namespace app\api\controller;
use app\common\controller\ApiBase;
use think\Controller;
use think\Request;
use think\Db;

class Kline extends Controller
{
    //K线图生成（createKline每分钟触发，generate_yes每晚触发）
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    public function createKline()
    {
        $time = day_time();
        $times = $this->kline_time();
        if ($times < $time) {
            Db::query("TRUNCATE TABLE sn_kline");
        }
        $currency = db('currency')->select();
        foreach ($currency as $k => $v) {
            $this->index($v['id']);
        }
        echo "success";
    }
    //每分钟生成K线
    public function index($cur_id)
    {
        //开盘价
        $previous_close = db('kline')->where('cur_id',$cur_id)->order('time desc')->value('close');
        if(!$previous_close&&$previous_close!=0){
            $previous_close = $this->query_yesterday($cur_id);
        }
        $coin_market = db('cur_market')->where('cur_id',$cur_id)->field('price_new as close,volume as vol,max_price as high,min_price as low')->find();
        if(!$coin_market){
            $coin_market['high'] = 0;
            $coin_market['low'] = 0;
            $coin_market['close'] = 0;
            $coin_market['vol'] = 0;
        }else{
            $update_data['price_new'] = 0;
            $update_data['max_price'] = 0;
            $update_data['min_price'] = 0;
            $update_data['volume'] = 0;
            $update_data['status'] = 0;
            db('cur_market')->where('cur_id',$cur_id)->update($update_data);
        }
        
        $this->minute($previous_close,$coin_market,$cur_id);
    }

    /**
     * 查询上一日时间
     * @param $code string 股票信息
     * @param $open string 默认信息
     * @return mixed
     */
    public function query_yesterday_time($cur_id)
    {
        $file = 'kline/'.$cur_id.'/day.csv';
        if (file_exists($file)) {
            $json_string = file_get_contents($file);
            $json_string = explode('|', $json_string);
            $json_string = $json_string[count($json_string) - 1];
            $data = json_decode($json_string, true);
            $open = $data[count($data) - 1]['time'];
        }
        return $open;
    }

    /**
     * 查询上一日收盘价格
     * @param $list array 股票信息
     * @param $open string 默认信息
     * @return mixed
     */
    private function query_yesterday($cur_id)
    {
        $file = 'kline/'.$cur_id.'/day.csv';
        if (file_exists($file)) {
            $json_string = file_get_contents($file);
            $json_string = explode('|', $json_string);
            $json_string = $json_string[count($json_string) - 1];
            $data = json_decode($json_string, true);
            $open = $data[count($data) - 1]['close'];
        }
        return $open;
    }

    /**
     * 查询K线图最后一条时间
     * @param $list array 股票信息
     * @param $open string 默认信息
     * @return mixed
     */
    private function kline_time()
    {
        $time = db('kline')->order('time desc')->value('time');
        return strtotime(date('Ymd',$time));;
    }

    /**
     * 生成一分钟K线图
     * @param string $open 开盘价格
     * @param array $data K线图数据
     */
    private function minute($open, $data,$cur_id)
    {
        $kline = $data;
        $kline['open'] = $open;
        $kline['time'] = time();
        $kline['cur_id'] = $cur_id;
        db('kline')->insert($kline);
    }

    public function generate_yes()
    {

        $currency = db('currency')->select();
        foreach ($currency as $k => $v) {
            $this->generate_data($v['id']);
        }
        $update_data['twenty_four_volume'] = 0;
        db('cur_market')->update($update_data);
        echo "success";
        
    }
    //每晚生成历史数据

    public function generate_data($cur_id)
    {
        $data_array = db('kline')->order('time asc')->select();
        if(!$data_array){
           $data_array = [];
        }
        $file = $this->file_data();
        foreach ($file as $key => $value) {
            $minute = $this->data_minute($data_array, $value['num']);
            $file_name = 'kline/'.$cur_id.'/'. $value['name'];
            $this->file_put($file_name,$minute);
            if ($value['num'] == 1440) {
                $this->month_data($minute,$cur_id);
                $this->week_data($minute,$cur_id);
            }
        }
        
    }

    /**
     * 要生成的历史文件
     * @return array
     */
    private function file_data()
    {
        $file = array();
        array_push($file, array('num' => 1, 'name' => 'minute.csv'));
        array_push($file, array('num' => 5, 'name' => 'five.csv'));
        array_push($file, array('num' => 15, 'name' => 'fifteen.csv'));
        array_push($file, array('num' => 30, 'name' => 'thirty.csv'));
        array_push($file, array('num' => 60, 'name' => 'sixty.csv'));
        array_push($file, array('num' => 1440, 'name' => 'day.csv'));
        return $file;
    }

    /**
     * 分时转换合并处理
     * @param $list array 数据信息
     * @param $times int 分割数量
     * @return array
     */
    private function data_minute($list, $times)
    {
        $list = array_chunk($list, $times);
        $data = array();
        foreach ($list as $key => $value) {
            $array = $this->highest_lowest($value);
            $arr = array();
            $arr['time'] = floatval($value[0]['time']);//时间
            $arr['open'] = floatval($value[0]['open']);
            $arr['low'] = floatval($array['highest']);//最小值
            $arr['high'] = floatval($array['lowest']);//最大值
            $arr['vol'] = floatval($array['vol']);//总量
            $arr['close'] = floatval($value[count($value) - 1]['close']);
            $data[] = $arr;
        }
        return $data;
    }

    public function highest_lowest($box)
    {    
        //求box high这一列的最大值
        $max = 0;
        foreach ($box as $key => $val) {
          $max = max($max , $val['high']);
        }
        //求box low这一列的最小值
        $min = 999999999;
        foreach ($box as $key => $val) {
          $min = min($min , $val['low']);
        }

        //求box vol这一列的总和
        $sum = 0;
        foreach ($box as $key => $val) {
          $sum = $sum + $val['vol'];
        }
        $return['highest'] = $max;
        $return['lowest'] = $min;
        $return['vol'] = $sum;
        return $return;
    }

    /**
     * 文件存储
     * @param $file string
     * @param $data
     */
    private function file_put($file, $data)
    {
        $json_string = json_encode($data, true);
        if (file_exists($file)) {
            file_put_contents($file, '|' . $json_string, FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($file, '|' . $json_string, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * 统计周数量
     * @param $code
     * @param $data
     */
    private function week_data($data,$cur_id)
    {
        $file_name = 'kline/'.$cur_id.'/week.csv';
        if (file_exists($file_name)) {
            $json_string = file_get_contents($file_name);
            $json_string = explode('|', $json_string);
            $list = $json_string[count($json_string) - 1];
            $list = json_decode($list, true);

            $open = $list[count($list) - 1];
            $week_time = intval(date('w', $open['time']));
            if (intval(date('w', $data[0]['time'])) == $week_time) {
                $key_number = count($list) - 1;
            } else {
                $key_number = -1;
            }
            if ($key_number == -1) {
                $json_string[count($json_string)] = json_encode($data, true);
            } else {
                $data[0]['close'] = $list[$key_number]['close'];
                $data[0]['low'] = $list[$key_number]['low'] < $data[0]['low'] ? $list[$key_number]['low'] : $data[0]['low'];
                $data[0]['high'] = $list[$key_number]['high'] > $data[0]['high'] ? $list[$key_number]['high'] : $data[0]['high'];
                $data[0]['vol'] = $list[$key_number]['vol'] + $data[0]['vol'];
                $json_string[count($json_string) - 1] = json_encode($data, true);
            }
            $json_string = implode('|', $json_string);
            file_put_contents($file_name, $json_string);
        } else {
            $json_string = json_encode($data, true);
            file_put_contents($file_name, '|' . $json_string);
        }
    }

    /**
     * 统计月数据
     * @param $code
     * @param $data
     */
    private function month_data($data,$cur_id)
    {
        $file_name = 'kline/'.$cur_id.'/month.csv';
        if (file_exists($file_name)) {
            $json_string = file_get_contents($file_name);
            $json_string = explode('|', $json_string);
            $list = $json_string[count($json_string) - 1];
            $list = json_decode($list, true);
            $open = $list[count($list) - 1];
            $week_time = intval(date('m', $open['time']));
            if (intval(date('m', $data[0]['time'])) == $week_time) {
                $key_number = count($list) - 1;
            } else {
                $key_number = -1;
            }
            if ($key_number == -1) {
                $json_string[count($json_string)] = json_encode($data, true);
            } else {
                $data[0]['close'] = $list[$key_number]['close'];
                $data[0]['low'] = $list[$key_number]['low'] < $data[0]['low'] ? $list[$key_number]['low'] : $data[0]['low'];
                $data[0]['high'] = $list[$key_number]['high'] > $data[0]['high'] ? $list[$key_number]['high'] : $data[0]['high'];
                $data[0]['vol'] = $list[$key_number]['vol'] + $data[0]['vol'];
                $json_string[count($json_string) - 1] = json_encode($data, true);
            }
            $json_string = implode('|', $json_string);
            file_put_contents($file_name, $json_string);
        } else {
            $json_string = json_encode($data, true);
            file_put_contents($file_name, '|' . $json_string);
        }
    }
}













