<?php
namespace Home\Controller;

use Think\Controller;
use \Org\Util\WeixinJssdk;
class IndexController extends CommonController
{
    public function index()
    {
        echo '网站主页';
    }
}
