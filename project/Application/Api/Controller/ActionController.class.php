<?php
namespace Api\Controller;
use Think\Controller;
use Think\Log;
use Org\Util\Gateway;

class ActionController extends Controller
{
    public function __construct()
    {
        Gateway::$registerAddress = '127.0.0.1:9504';
    }
    // 接收信息
    public function receive()
    {
        $message = I('post.');
        $client_id = $message['client_id'];
        unset($message['client_id']);

        // 判断数据传输的对象
        if( $message['soure']=='TCP'){
            $this->gettcp($client_id, $message);
        } else {
            $this->getws($client_id, $message);
        }
    }

    // 设备消息处理
    public function gettcp($client_id, $message)
    {
        Log::write(json_encode($message), '接收设备消息');
        if( empty( Gateway::getSession($client_id) ) ){
            Gateway::setSession($client_id, $message);
        } else {
            $res = Gateway::getSession($client_id);

            $message['DeviceID'] = $res['DeviceID'];
        }

        if($message['PackType'] == 'login'){

            Gateway::bindUid($client_id, $message['DeviceID']);

        }

        switch ($message['PackType']) {
            case 'login':
                Log::write(json_encode($message),'接收登陆信息');
                $this->loginAction($message);
                break;
            // 设备设置
            case 'Select':
                $this->selectAction($message);
                break;
            case 'Requestwater':
                $message = $this->RequesAction($client_id, $message);
                break;
            case 'Stopwater':
                $this->stopAction($client_id, $message);
                break;
            default:
                # code...
                break;
        }

        if( isset($message['DeviceID']) ){
            if( Gateway::getClientCountByGroup($message['DeviceID']) > 0 ){
                Gateway::sendToGroup( $message['DeviceID'], json_encode($message) );
            }
        }
        Gateway::sendToClient($client_id, $message);
    }

    // json数据处理
    public function getws($client_id, $message)
    {
        Log::write(json_encode($message), '接收客户消息');
        if( $message['PackType'] == 'login' ){
            Gateway::joinGroup( $client_id, $message['DeviceID'] );
        } else {
            Gateway::sendToUid($message['DeviceID'], $message);
        }
    }

    // 登陆数据处理
    public function loginAction($message)
    {
        $data = [
            'DataCmd'      => $message['DataCmd'],
            'Device'       => $message['Device'],
            'PackType'     => $message['PackType'],
            'AliveStause'  => $message['AliveStause'],
            'DeviceType'   => $message['DeviceType'],
            'DeviceID'     => $message['DeviceID'],
            'ICCID'        => $message['ICCID'],
            'CSQ'          => $message['CSQ'],
            'Loaction'     => $message['Loaction']
        ];
        Log::write(json_encode($data), '获取设备信息');die;
        $devices_id = M('devices')->where("device_code={$message['DeviceID']}")->getField('id');
        Log::write(json_encode($devices_id), '获取SQL输出语句');
        $status_id  = M('devices_statu')->where("DeviceID={$message['DeviceID']}")->getField('id');
        Log::write(json_encode($devices_id),'登陆信息处理完成');die;
        if( empty($status_id) ){
            $res = $this->saveData($data);
            if($res){
                $data['updatetime'] = time();
                $data['device_status'] = 1;
                $result = M('devices')->where('device_code=' . $message['DeviceID'])->save($data);
            }
        } else {
            $res = $this->updateData($status_id, $data);
        }

    }

    // 设备自动回复处理
    public function selectAction($message)
    {
        $data = [
            'DeviceStause' => $message['DeviceStause'],
            'ReFlow'       => $message['ReFlow'],
            'Reday'        => $message['Reday'],
            'SumFlow'      => $message['SumFlow'],
            'SumDay'       => $message['SumDay'],
            'RawTDS'       => $message['RawTDS'],
            'PureTDS'      => $message['PureTDS'],
            'Temperature'  => $message['Temperature'],
            'FilerNum'     => $message['FilerNum'],
            'LeasingMode'  => $message['LeasingMode'],
        ];

        if( $message['FilerNum'] != null ){
            $res = $this->filterAction($message);

            $data = array_merge( $data, $res );
        }

        $status_id = M('devices_statu')->where("DeviceID=" . $message['DeviceID'])->getField('id');
        $this->updateData($status_id, $data);
    }


    // 存储数据 将数据存到 devices_statu表中
    public function saveData($data)
    {
        $data['addtime'] = time();
        return M('devices_statu')->add($data);
    }

    // 更新数据
    public function updateData($id, $data)
    {
        $data['updatetime'] = time();
        return M('devices_statu')->where("id={$id}")->save($data);
    }

    // 滤芯处理
    public function filterAction($message)
    {
        $data = array();
        for( $i = 1; $i <= $message['FilerNum']; $i ++)
        {
            $data['ReFlowFilter' . $i]   = $message['ReFlowFilter' . $i];
            $data['ReDayFilter' . $i]    = $message['ReDayFilter' . $i];
        }
        return $data;
    }

}
