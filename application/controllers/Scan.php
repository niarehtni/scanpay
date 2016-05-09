<?php
require '../vendor/autoload.php';
use EasyWeChat\Payment\Order;


class ScanController extends Core
{
    private $option;

    public function init()
    {
        parent::init();
        $this->scanMod = new ScanModel();
        
    }

    public function create_productAction()
    {
        $wechat = ( $this->_post['wechat'] ?? $this->Response->error('40016')) ? : $this->Response->error('40022');
        $body = ( $this->_post['body'] ?? $this->Response->error('40016')) ? : $this->Response->error('40019');
        $detail = ( $this->_post['detail'] ?? $this->Response->error('40016') ) ? : $this->Response->error('40020');
        $total_fee = ( $this->_post['total_fee'] ?? $this->Response->error('40016') ) ? : $this->Response->error('40021');
        if (! $weData = $this->scanMod->getWechat($wechat, 'name')) {
            $this->Response->error('40023');
        }
        $payLib = new Pay($weData['app_id'], $weData['mch_id'], $weData['key']);
        $productId = $this->Common->random_string('alnum', 32);
        $qrUrl = $payLib->createQrUrl($productId);
        $product = [
            'body' => $body,
            'detail' => $detail,
            'total_fee' => $total_fee,
            'product_id' => $productId,
            'qr_url' => $qrUrl,
            'create_time' => time(),
            'update_time' => time(),
            
        ];
        if($this->scanMod->addProduct($product)) {
            $data = [
                'qr'=>$qrUrl,
                'createTime'=>time(),
            ];
            $this->Response->success($data);

        }
        
        
        
    }

    public function orderAction()
    {
        //获取微信的请求参数
        $postStr = file_get_contents('php://input');  
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $productId = strval($postObj->product_id);
        $openId = strval($postObj->openid);
        $appId = strval($postObj->appid);

        // 根据微信发来的product id 获取商品信息
        if (!$productData = $this->scanMod->getProduct($productId)) {
            error_log('product is no exit: product id ='.$productId);
            exit();
        }
        // 根据微信发来的app id获取微信数据 
        if ( ! $weData = $this->scanMod->getWechat($appId) ) {
            error_log('wechat data is no exit: app id ='.$appId);
            exit();
        }
        $attributes = [
            'body'             => $productData['body'],
            'detail'           => $productData['detail'],
            'out_trade_no'     => $this->Common->random_string('alnum', 32),
            'product_id'       => $productId,
            'openid'           => $openId,
            'total_fee'        => $productData['total_fee'],
            // 'notify_url'       => 'http://scanpay.vzhen.com/order_notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'trade_type'       => 'NATIVE',
            'appid'            => $appId,
            'mch_id'           => $weData['mch_id'],
        ];

        $order = new Order($attributes);
        $payLib = new Pay($weData['app_id'], $weData['mch_id'], $weData['key']);
        $result = $payLib->createOrder($order);
        error_log('DEBUG: '.$result);
        error_log('DEBUG: '.json_encode($attributes));
        // 订单创建成功,开始支付
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $attributes['paid'] = 0;
            $attributes['create_time'] = time();
            $attributes['update_time'] = time();
        
            if ($this->scanMod->addOrder($attributes)) {
                    $reply = [
                            'return_code' =>$result->return_code,
                            // 'return_msg' =>$result->return_msg,
                            'appid'       =>$_SERVER['APP_ID'],
                            'mch_id'        => $_SERVER['MER_ID'],
                            'nonce_str' =>$result->nonce_str,
                            'prepay_id' => $result->prepay_id,
                            'result_code' =>$result->result_code,
                            // 'err_code_des' =>$result->err_code_des ?? '',
                    ];
                    $reply['sign'] = \EasyWeChat\Payment\generate_sign($reply, $_SERVER['KEY'], 'md5'); 
                    $xml = $this->Common->toXml($reply);
                    error_log('DEBUG order xml :'.$xml);
                    echo $xml;

            }
        }
    }

    public function order_notifyAction()
    {
        //获取微信的请求参数
        $postStr = file_get_contents('php://input');  
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        $appId = strval($postObj->appid);
        // 根据微信发来的app id获取微信数据 
        if ( ! $weData = $this->scanMod->getWechat($appId) ) {
            error_log('wechat data is no exit: app id ='.$appId);
            exit();
        }
        $payLib = new Pay($weData['app_id'], $weData['mch_id'], $weData['key']);
        
        $response = $payLib->notify->handleNotify(function($notify, $successful){
            // 你的逻辑
            error_log("DEBUG notify :".$notify);
            if (!$this->scanMod->getOrder($notify->out_trade_no)) {
                error_log("DEBUG notify order not exist:".$notify->out_trade_no);
                return 'Order not exist';
            }
            if ($successful) {
                $this->scanMod->updateOrderPaid($notify->out_trade_no);
            } 
            return true; // 或者错误消息
        }); 

    }

}
