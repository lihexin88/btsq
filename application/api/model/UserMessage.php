<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/15
 * Time: 10:45
 */

namespace app\api\model;

use think\Exception;
use think\Lang;
use think\Model;
use think\Db;

class UserMessage extends Model
{

    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量

    // 时间字段取出后的默认时间格式
    protected $dateFormat;

    /**
     * 记录"通知中心"
     * @param $data
     * @param $userinfo
     * @return array
     * @throws \think\exception\DbException
     */
    public function create_user_message($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];
        $in_message['uid'] = $userinfo['id'];
        $in_message['create_time'] = time();
        Language::lang($data['title'], $userinfo);
        Language::lang($data['first_content'], $userinfo);
        Language::lang($data['second_content'], $userinfo);
        $in_message['title'] = $data['title'];
        $in_message['first_content'] = $data['first_content'];
        $in_message['second_content'] = $data['second_content'];
        // 惩罚站内信
        if ($data['third_content']) {
            Language::lang($data['third_content'], $userinfo);
            $in_message['third_content'] = $data['third_content'];
        }
        // 订单号
        if ($data['order']) {
            $in_message['order'] = $data['order'];
        }
        if (false === $this->insert($in_message)) {
            return ['code' => 0, 'msg' => '记录用户信息失败'];
        }
        return ['code' => 1];
    }

    /**
     * 获取"通知中心"列表
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function message_list($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

//        if ($data['page']) {
//            $page_start = $data['page'] * 20 - 20;
//        } else {
//            $page_start = 0;
//        }
//        $page_end = 20;

        if (!$data['visit']) {
            $data['visit'] = 1;
        }

        // 获取是否访问
        $message_where['uid'] = $userinfo['id'];
        $message_where['visit'] = $data['visit'] ? $data['visit'] : 2;

        $list = $this->where($message_where)->order('create_time desc')->select();  // limit($page_start,$page_end) ->
        foreach ($list as $k => $v) {
            $content_left = Language::lang($v['first_content'], $userinfo);
            $content_right = Language::lang($v['second_content'], $userinfo);
            if ($v['third_content']) {
                $content_last = Language::lang($v['third_content'], $userinfo);
                $list[$k]['content'] = $content_left . ' ' . $v['create_time'] . ' ' . $content_right . ' ' . $v['order'] . ' ' . $content_last;
            } else {
                $list[$k]['content'] = $content_left . ' ' . $v['create_time'] . ' ' . $content_right;
            }
            unset($v['first_content']);
            unset($v['second_content']);
        }
        return $list;
    }

    /**
     * 获取"通知中心"详情
     * @param $datas
     * @return array
     */
    public function message_detail($datas)
    {
        $data = $datas['data'];
        $userinfo = $datas['user_info'];

        if (!$data['id']) {
            return ['code' => 0, 'msg' => '未获取通知信息'];
        }

        Db::startTrans();
        try {
            // 获取通知详情
            $detail = $this->where('id', $data['id'])->find();
            if (!$detail) {
                throw new Exception('获取通知详情失败');
            }
            $content_left = Language::lang($detail['first_content'], $userinfo);
            $content_right = language::lang($detail['second_content'], $userinfo);
            if ($detail['third_content']) {
                $content_last = Language::lang($detail['third_content'], $userinfo);
                $detail['content'] = $content_left . ' ' . $detail['create_time'] . ' ' . $content_right . ' ' . $detail['order'] . ' ' . $content_last;
            } else {
                $detail['content'] = $content_left . ' ' . $detail['create_time'] . ' ' . $content_right;
            }
            unset($detail['first_content']);
            unset($detail['second_content']);

            // 修改访问状态
            if (false === $this->where('id', $data['id'])->update(['visit' => 2])) {
                throw new Exception('修改访问状态失败');
            }

            Db::commit();
            return ['code' => 1, 'data' => $detail];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

}



