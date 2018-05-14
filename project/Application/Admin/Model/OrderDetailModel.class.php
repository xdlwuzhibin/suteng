<?php
namespace Admin\Model;
use Think\Model;
class OrderDetailModel extends BaseModel
{
    public function getInfo($where=array())
    {
        $data = $this
            ->where($where)
            ->alias('od')
            ->join('__SHOP_ORDER__ o ON od.order_id=o.order_id', 'LEFT')
            ->join('__GOODS__ g ON od.gid=g.id', 'LEFT')
            ->join('__GOODS_DETAIL__ gd ON g.id=gd.gid','LEFT')
            ->join('__PIC__ p ON g.id=p.gid', 'LEFT')
            ->join('__ADDRESS__ a ON o.address_id=a.id', 'LEFT')
            ->field('g.name gname,od.num,od.price,od.express,od.express_name,gd.desc productbrief,a.name,a.phone,a.address,p.path,o.status os')
            ->select();
        return $data;
    }

    public function getLvxinInfo($where=array())
    {
        $data = $this
            ->where($where)
            ->alias('od')
            ->join('__SHOP_ORDER__ o ON od.order_id=o.order_id', 'LEFT')
            ->join('__FILTERS__ f ON f.id = od.gid','LEFT')
            ->join('__ADDRESS__ a ON o.address_id=a.id', 'LEFT')
            ->field('f.filtername gname,od.num,od.price,od.express,od.express_name,f.introduce productbrief,a.name,a.phone,a.address,f.picpath path,o.status os')
            ->select();
        return $data;
    }
}