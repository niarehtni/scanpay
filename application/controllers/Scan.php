<?php
require '../vendor/autoload.php';
use EasyWeChat\Payment\Order;


class ScanController extends Core
{
    private $option;

    public function init()
    {
        parent::init();
        
    }

    public function create_itemAction()
    {
        $payLib = new Pay();
        $productId = $this->Common->random_string('alnum', 32);
        $qrUrl = $payLib->createQrUrl($productId);
        $data = [
            'qr'=>$qrUrl,
            'createTime'=>time(),
        ];
        $this->Response->success($data);
        
        /*$from = ( $this->_post['from'] ?? $this->Response->error('40016')) ? : $this->Response->error('40019');
        $to = ( $this->_post['to'] ?? $this->Response->error('40016') ) ? : $this->Response->error('40020');
        $date = ( $this->_post['date'] ?? date('Y-m-d')) ? : date('Y-m-d');
        if ( ! $this->Common->validateDate($date, 'Y-m-d') ) $this->Response->error('40022');
        $flightData = $this->Trip->air($from, $to, $date); 
        echo '<pre>';print_r($flightData);echo '</pre>';exit(); 
        if ($flightData) {
            $this->Response->success($flightData);
        }*/
        
    }

    public function orderAction()
    {
        //获取微信的请求参数
        $postStr = file_get_contents('php://input');  
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $productId = strval($postObj->product_id);
        $openId = strval($postObj->openid);

        $attributes = [
            'body'             => 'iPad mini 16G 白色',
            'detail'           => 'iPad mini 16G 白色',
            'out_trade_no'     => $productId,
            'openid'     => $openId,
            'total_fee'        => 1,
            'notify_url'       => 'http://scanpay.vzhen.com/order_notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type'       => 'NATIVE',
            // ...
        ];

        $order = new Order($attributes);
        $payLib = new Pay();
        $result = $payLib->createOrder($order);
        error_log('DEBUG: '.$result->return_msg);
        error_log('DEBUG: '.json_encode($attributes));
echo '<pre>';print_r($result->return_msg);echo '</pre>';exit(); 
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
        }
    }

    public function order_notify()
    {

    }

}
