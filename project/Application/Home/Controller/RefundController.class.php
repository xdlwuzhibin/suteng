<?php
namespace Home\Controller;
use Org\Util\Date;
use Org\Util\Strings;
/**
 * [退款及退款退货]
 */
class RefundController extends CommonController
{

    public function showGoods()
    {
        if (IS_AJAX) {
           $data = D('Refund')->relation(['goods'])->where(['uid'=>$_SESSION['user']['id']])->select();

            foreach ($data as $key => $value) {
                foreach ($value['goods'] as $k => $val) {                                  
                    $data[$key]['goods'][$k] = M('order_detail')
                        ->alias('d')
                        ->where(['d.order_id'=>$val['oid'],'d.gid'=>(int)$val['gid']])
                        ->join('__GOODS__ g ON g.id = d.gid','LEFT')
                        ->join('__GOODS_DETAIL__ g_d ON g.id = g_d.gid','LEFT')
                        ->join('__PIC__ p ON g.id = p.gid','LEFT')
                        ->field(array('p.path'=>'orderimg','g.name'=>'productname','g.desc'=>'productbrief','d.gid','d.price'=>'price','d.num'=>'productnumber','g_d.is_install'=>'is_install','g_d.is_hire'=>'is_hire'))
                        ->find();
               } 
            }
            if ($data) {
               return $this->ajaxReturn(['code'=>200,'data'=>$data]);
            }else{
               return $this->ajaxReturn(['code'=>400,'msg'=>'没有数据']);
            } 
        }
    }

    /**
     * 退款退货申请
     * @return [type] [description]
     */
    public function create()
    {
        $post = I('post.');
        $order_id = $post['order_id'];
        // 商品
        $data['serial_num'] = $this->getNumber();
        $data['uid']        = $_SESSION['user']['id'];
        $data['total_amount'] = $post['total_amount'];
        $data['method']     = $post['method'];
        $data['reason']     = $post['reason'];
        $data['description'] = $post['description'];
        $data['create_time'] = time();      
        foreach ($post['gid'] as $key => $value) {
            $data['goods'][$key] = ['oid'=>$post['order_id'],'gid'=>$value,'amount'=>$post['price']];
            $res = D('RefundGoods')->where(['oid'=>$order_id,'gid'=>$value])->select();
            if($res){
                return $this->ajaxReturn(['code'=>601,'msg'=>'商品已经退过款了']);
            }
        }
        
        

        try {
            $refund = D('Refund');
            $savePath = 'Uploads/pic/';
            if($data['method'] == 2){            
                // 二进制文件上传简单处理
                if (!empty($_FILES["UploadForm"])) {
                    foreach ($_FILES["UploadForm"]["tmp_name"] as $key => $value) {
                        $image = $_FILES["UploadForm"]["tmp_name"][$key];
                        $imageSize = $_FILES["UploadForm"]["size"][$key];
                        $info[] = $this->upload($image,$imageSize);                        
                    }
                }else{
                    E('没有文件上传', 602);
                }   
                
                if(!$info) {// 上传错误提示错误信息
                    E('上传错误',606);
                }
                if(!(count($info) <= 3)){
                    E('只能添加三张图片',604);
                }
                 foreach ($info as $k => $val) {
                    $path .= $val.'|';
                }
                $data['pic'] = $path;
            }
            
            $refund->startTrans();
            // print_r($data);die;                    
            $result = D('Refund')->relation(true)->add($data);
            // 将订单状态更改为退货处理中
            D('order_detail')->where(['order_id'=>$order_id])->setField(['status'=>6]);
            if($result){
                $refund->commit();
                E('申请成功', 200);
                // $this->success('评论成功');
            } else {
                $comment->rollback();
                E('申请失败', 603);
                // $this->error('评论失败');
            }
        } catch (\Exception $e) {
            $this->rmfiles($info);
            $err = [
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
            $this->ajaxReturn($err);
            // $this->error('评论失败');                     
        }
    }

    /**
     * 退货填写物流信息
     * @return [type] [description]
     */
    public function logistics()
    {   
        if (IS_AJAX) {
            $data['rf_id'] = 2;
            $data['name']   = '顺风快递';
            $data['number'] = '65891546455546';
            $res = D('refund_logistics')->data($data)->add();
            if ($res) {
                return $this->ajaxReturn(['code'=>200,'msg'=>'发货成功']);
            } else {
                return $this->ajaxReturn(['code'=>400,'msg'=>'发货失败']);
            }
        }       
    }

    /**
     * 生成工单编号
     * @return [type] [description]
     */
    protected function getNumber()
    {
        $string = new Strings;
        // $str1 = $string->randString(3,0);   // 生成字母随机字符
        $str2 = $string->randString(5,1);   // 生成数字随机字符
        $str3 = $string->randString(3,1);   // 生成数字随机字符
        $number = time().$str2.$str3;
        if (D('Refund')->where(['serial_num'=>$number])->find()) {
            return $this->getNumber();
        }
        return $number;
    }


    /**
     * [删除文件]
     * @param  Array $files 文件路径数组
     * @return Boolean        
     */
    public function rmfiles($files)
    {
        foreach ($files as $key => $value) {
            return unlink($value);
        }
    }

    /**
     * 二进制流图片上传
     * @param  [type] $image     二进制图片
     * @param  [type] $imageSize 图片大小
     * @return mix            图片路径，false
     */
    public function upload($image,$imageSize)
    {
        $key = fopen($image, "r");
        $file = fread($key, $imageSize); //二进制数据流
        fclose ( $key );

        do{
             //设置目录+图片完整路径
            $save_name = md5 (time().mt_rand(100000,9999999)).'.png';
            $save_dir = './Uploads/pic';
            ! is_dir ( $save_dir ) && mkdir ( $save_dir,0777,1 );
            $save_fullpath = $save_dir . '/' . $save_name;
        }while (file_exists());

        @$fp = fopen ( $save_fullpath, 'w+' );
        
        if (fwrite ( $fp, $file ) != false) {
            return $save_fullpath;
        } else {
            return false;
        }
        fclose ( $fp );        
    }
}