<?php
namespace Home\Controller;

class ShoppingCartController extends CommonController
{

    /**
     * [index 购物车主页]
     * @return [type] [description]
     */
    public function index()
    {
        $cart = D('Cart');
        $where['c.uid'] = session('user.id');
        $data = $cart->getCart($where);
        foreach($data as $val){
    		$key = $val['gid'];
    		if(isset($arr[$key])) {
    			$arr[$key]['attr'] .= $val['attr'].':'.$val['val'].'|';
    		} else {
                $arr[$key] = $val;
    			$arr[$key]['attr'] = $val['attr'].':'.$val['val'].'|';
    		}
    	}
    	$data = array_values($arr);
        $assign = [
            'data' => $data,
        ];
        $this->assign($assign);
        $this->display();
    }

    // 加入购物车
    public function shopAdd()
    {
        try {
            $cart = D('Cart');
            $data['num'] = I('post.num');
            $data['gid'] = I('post.gid');
            $data['uid'] = session('user.id');
            $res = $cart->create();
            if(!$res) E($cart->getError(),603);
            $res = $cart->add();
            if($res){
                E('加入购物车', 200);
            } else {
                E('无法加入购物车', 603);
            }
        } catch (\Exception $e) {

        }

    }
}
