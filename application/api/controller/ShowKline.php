<?php

namespace app\api\controller;
use app\common\controller\ApiBase;
use think\Request;

class ShowKline extends ApiBase
{
    //K线图显示
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * K线数据
     * @param $data
     * @return array
     */
    public function line_graph($type='',$cur_id='')
    {
        $type = empty($type) ? '8' : $type;
        $cur_id = empty($cur_id) ? '1' : $cur_id;
        $data = $this->k_line($type,$cur_id);
        return json($data);
    }

    /**
     * 获取分时图
     * @param $code string 股票代码
     * @param $type int 类型
     * @return array
     */
    private function k_line($type,$cur_id)
    {
        $prices = db('kline')->where('cur_id',$cur_id)->order('time asc')->select();
        if ($prices) {
            $prices = $prices;
        } else {
            $prices = array();
        }
        if ($type == 1) {
            $day = $this->data_minute($prices, count($prices));
            $file = "kline/".$cur_id."/day.csv";
            $data = $this->file_data($file, $type, 174, $day);
        } elseif ($type == 2) {
            $day = $this->data_minute($prices, count($prices));
            $file = "kline/".$cur_id."/week.csv";
            $data = $this->week_month($file, $type, 1000, $day);
        } elseif ($type == 3) {
            $day = $this->data_minute($prices, count($prices));
            $file = "kline/".$cur_id."/month.csv";
            $data = $this->week_month($file, $type, 1000, $day);
        } elseif ($type == 4) {
            $day = $this->data_minute($prices, 1);
            $file = "kline/".$cur_id."/minute.csv";
            $data = $this->file_data($file, $type, 1, $day);
        } elseif ($type == 5) {
            $file = "kline/".$cur_id."/five.csv";
            $day = $this->data_minute($prices, 5);
            $data = $this->file_data($file, $type, 3, $day);
        } elseif ($type == 6) {
            $file = "kline/".$cur_id."/fifteen.csv";
            $day = $this->data_minute($prices, 15);
            $data = $this->file_data($file, $type, 10, $day);
        } elseif ($type == 7) {
            $file = "kline/".$cur_id."/thirty.csv";
            $day = $this->data_minute($prices, 30);
            $data = $this->file_data($file, $type, 15, $day);
        } elseif ($type == 8) {
            $day = $this->data_minute($prices, 60);
            $file = "kline/".$cur_id."/sixty.csv";
            $data = $this->file_data($file, $type, 30, $day);
        } else {
            $file = "kline/".$cur_id."/day.csv";
            $day = $this->data_minute($prices, count($prices));
            $data = $this->file_data($file, $type, 174, $day);
        };
        return $data;
    }

    private function week_month($file, $type, $number, $day)
    {
        $json_string = FileLastLines($file, $number, '|');
        $json_string = explode('|', $json_string);
        $list = array();
        foreach ($json_string as $key => $value) {
            if ($value) {
                $value = json_decode($value, true);
                foreach ($value as $k => $v) {
                    array_push($list, $v);
                }
            }
        }
        if ($day) {
            $day = $day[0];
            $open = $list[count($list) - 1];
            $week_time = intval(date('W', $open['time']));
            if (intval(date('W', $day['time'])) == $week_time) {
                $key_number = count($list) - 1;
            } else {
                $key_number = -1;
            }
            if ($key_number == -1) {
                $list[count($list)] = $day;
            } else {
                $day['open'] = $list[$key_number]['open'];
                $day['low'] = $list[$key_number]['low'] < $day['low'] ? $list[$key_number]['low'] : $day['low'];
                $day['high'] = $list[$key_number]['high'] > $day['high'] ? $list[$key_number]['high'] : $day['high'];
                $day['vol'] = $list[$key_number]['vol'] + $day['vol'];
                $list[count($list) - 1] = $day;
            }
        }
        foreach ($list as $key => $value) {
            $value['time'] = format_time($value['time'], $type);
            array_unshift($value, $value['time']);
            array_pop($value);
            $list[$key] = $value;
        }
        return $list;
    }

    /**
     * 分时转换合并处理
     * @param $list array 数据信息
     * @param $times int 分割
     * @return array
     */
    private function data_minute($list, $times)
    {
        $data = array();
        if ($list) {
            $list = array_chunk($list, $times);
            foreach ($list as $key => $value) {
                $number = count($value) - 1;
                $arr = array();
                $volume = 0;
                $highest = array();
                $lowest = array();
                foreach ($value as $k => $v) {
                    $volume += $v['vol'];
                    array_push($highest, $v['high']);
                    array_push($lowest, $v['low']);
                }

                $arr['time'] = floatval($value[$number]['time']);
                $arr['open'] = floatval($value[0]['open']);
                $arr['high'] = floatval(max($highest));
                $arr['low'] = floatval(min($lowest));
                $arr['close'] = floatval($value[$number]['close']);
                $arr['vol'] = floatval($volume);
                $data[] = $arr;
            }
        }
        return $data;
    }

    /**
     * 文件和当日信息合并
     * @param $file string 文件名
     * @param $type int 软件类型
     * @param $number int 总条数
     * @param $day_data array 今日数据
     * @return array
     */
    private function file_data($file, $type, $number, $day_data)
    {
        $json_string = FileLastLines($file, $number, '|');
        $json_string = explode('|', $json_string);
        $list = array();

        foreach ($json_string as $key => $value) {
            if ($value) {
                $value = json_decode($value, true);
                foreach ($value as $k => $v) {
                    array_push($list, $v);
                }
            }
        }

        foreach ($day_data as $key => $value) {
            array_push($list, $value);
        }
        foreach ($list as $key => $value) {
            $list[$key]['time'] = format_time($value['time'], $type);
            $list[$key]['open'] = number_format($value['open'], 2);
            $list[$key]['low'] = number_format($value['low'], 2);
            $list[$key]['high'] = number_format($value['high'], 2);
            $list[$key]['close'] = number_format($value['close'], 2);
            $list[$key]['vol'] = number_format($value['vol'], 5);
        }

        return $list;
    }
}













