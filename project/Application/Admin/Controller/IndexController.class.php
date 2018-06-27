<?php
namespace Admin\Controller;
use Admin\Controller\CommonController;
use Think\Controller;

class IndexController extends CommonController
{
    public function index()
    {
        $this->display();
    }

    public function welcome()
    {
    	if (IS_AJAX) {
    		// 充值数额统计数量 （本月列表显示）
	    	$flows = D('Flow')->getTotalByEveryDay();

	    	$devices = D('Devices')->getTotalByEveryDay();
	    	// 订单数量统计
	    	$orders = D('ShopOrder')->field('count(distinct(order_id)) as total')->select();
	    	// 保修数量统计->保修列表 
	    	$repairs['total'] = D('Repair')->count();	    	

	    	// 总用户量统计->用户列表
	    	$feeds['total'] = D('Users')->count();

	    	$data = [
	    		'flows' => $flows,
	    		'devices'=> $devices,
	    		'orders' => $orders[0],
	    		'repairs' => $repairs,
	    		'feeds' => $feeds
	    	];
	    	// print_r($data);
	    	$this->ajaxReturn($data);
    	} else {
    		$this->display();
    	}       
    }
}