<?php
namespace Common\Lib\WxPay;
use Common\Lib\WxPay\WxPayNotify;
use Common\Lib\WxPay\WxPayData\WxPayOrderQuery;

class PayNotifyCallBack extends WxPayNotify
{
    //查询订单
    public function Queryorder($transaction_id, $config)
    {
        $input = new WxPayOrderQuery($config);
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input, 6, $config);
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }
    
    //重写回调处理函数
    public function NotifyProcess($data, &$msg, $config, $obj, $callback)
    {
        // Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();
        
        if(!array_key_exists("transaction_id", $data)){
            $data = false;
        }
        //查询订单，判断订单真实性
        if(!$this->Queryorder($data["transaction_id"], $config)){
            $data = false;
        }
        if(method_exists($obj, $callback)){
            return $obj->$callback($data);
        }else{
            return false;
        }
    }
}