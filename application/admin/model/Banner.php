<?php

namespace app\admin\model;

use app\common\model\Base;
use think\Request;
use think\db;

class Banner extends Base
{
    /**
     *添加时自动完成
     */
    protected $insert = array('sort' => 0, 'state' => 1);

    /**
     *更新时自动完成
     */
    protected $update = [];
    const PAGE_LIMIT = '10';//用户表分页限制
    const PAGE_SHOW = '10';//显示分页菜单数量

    /**
     * 获取列表
     * @param  array $map 查询条件
     * @param  string $p 页码
     * @return array      返回列表
     */
    public function infoList($map, $p)
    {
        $request = Request::instance();
        $list = $this->where($map)->order('sort desc')->page($p, self::PAGE_LIMIT)->select()->toArray();
//      服务器地址拼接，本地直接去掉
        foreach ($list as $k => $v) {
            $list[$k]['chs_url'] = $v['chs_url'];
//            $list[$k]['chs_url'] = "/btsq/public" . $v['chs_url'];
            $list[$k]['cht_url'] = $v['cht_url'];
//            $list[$k]['cht_url'] = "/btsq/public" . $v['cht_url'];
            $list[$k]['en_url'] =  $v['en_url'];
//            $list[$k]['en_url'] = "/btsq/public" . $v['en_url'];
        
        }
        $return['count'] = $this->where($map)->count();
        $return['list'] = $list;
        $return['page'] = boot_page($return['count'], self::PAGE_LIMIT, self::PAGE_SHOW, $p, $request->action());
        return $return;
    }

    /**
     * 新增/修改
     * @param  array $data 传入信息
     */
    public function saveInfo($data)
    {
        if (array_key_exists('id', $data)) {
            $id = $data['id'];
            if (!empty($id)) {
                $where = true;
            } else {
                $where = false;
            }
        } else {
            $where = false;
        }
        $Banner = new Banner;
        $result = $Banner->allowField(true)->isUpdate($where)->save($data);
        if (false === $result) {
            return ['status' => 0, 'info' => $AuthGroup->getError()];
        } else {
            return array('status' => 1, 'info' => '保存成功', 'url' => url('index'));
        }
    }

    /**
     * 删除
     * @param  string $id ID
     */
    public function deleteInfo($id)
    {
        $this_banner = $this->get(['id' => $id])->toArray();
//        删除本地文件
        foreach ($this_banner as $k => $v) {
            if (in_array($k, ["chs_url", "cht_url", "en_url"])) {
                $filename = ROOT_PATH . "public" . $v;
                $filename = str_replace("\\","/",$filename);
                if (file_exists($filename)) {
                    unlink($filename);
                }
            }
        }
        if ($this->where(array('id' => $id))->delete()) {
            return ['status' => 1, 'info' => '删除成功'];
        } else {
            return ['status' => 0, 'info' => '删除失败,请重试'];
        }
    }

    /**
     * 排序
     */
    public function changeSort($datas)
    {
        if ($this->where(array('id' => $datas['id']))->update(array('sort' => $datas['sort']))) {
            return array('status' => 6, 'info' => '更新成功!');
        } else {
            return array('status' => 5, 'info' => '更新失败:数据未变动');
        }
    }

    /**
     * 改变状态
     * @param  array $data 传入数组
     */
    public function changeState($data)
    {
        if ($this->where(array('id' => $data['id']))->update(array('state' => $data['state']))) {
            return array('status' => 1, 'info' => '更改状态成功');
        } else {
            return array('status' => 0, 'info' => '更改状态失败');
        }
    }

    /**
     * 根据查询条件获取信息
     * @param string $map [查询条件]
     * @return mixed
     */
    public function infodata($map)
    {
        $list = $this->where($map)->find();
        if (!is_null($list)) {
            return $list->toArray();
        }
        return false;
    }

}