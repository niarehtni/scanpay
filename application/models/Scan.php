<?php 

class ScanModel extends Model {

    const TBL_PRODUCT = 'product';
    const TBL_WECHAT = 'wechat';
    const TBL_ORDER = 'order_all';

    public function addProduct($data)
    {
        $this->_db->where('product_id', $data['product_id']);
        if (!$this->_db->getOne(SELF::TBL_PRODUCT)){
            if ( ! $id = $this->_db->insert(self::TBL_PRODUCT, $data) ) {
                 error_log('insert product data error '. $this->_db->getLastError());
                 exit();
            }
        }
        return TRUE;
    }

    public function getProduct($productId)
    {
        $this->_db->where('product_id', $productId);
        $data = $this->_db->getOne(SELF::TBL_PRODUCT);
        if(!$data) {
            return FALSE;
        } 
        return $data;

    }

    public function getWechat($val,$row='appid')
    {
        if($row=='appid') {
            $this->_db->where('app_id', $val);
        }else{
            $this->_db->where('name', $val);

        }
        $data = $this->_db->getOne(SELF::TBL_WECHAT);
        if(!$data) {
            return FALSE;
        } 
        return $data;

    }

    public function addOrder($data)
    {
        $this->_db->where('out_trade_no', $data['out_trade_no']);
        if (!$this->_db->getOne(SELF::TBL_ORDER)){
            if ( ! $id = $this->_db->insert(self::TBL_ORDER, $data) ) {
                 error_log('insert order data error '. $this->_db->getLastError());
                 exit();
            }
        }
        return TRUE;

    }

    public function getOrder($out_trade_no)
    {
        $this->_db->where('out_trade_no', $out_trade_no);
        $data = $this->_db->getOne(SELF::TBL_ORDER);
        if(!$data) {
            return FALSE;
        } 
        return $data;

    }

    public function updateOrderPaid($out_trade_no)
    {
        $this->_db->where('out_trade_no', $out_trade_no);
        $data = [
            'paid' => 1,
            'update_time' => time(),
        ];
        if ($this->_db->update(SELF::TBL_ORDER, $data) ) {
            return TRUE;
        }
        else
        {
             error_log('update order paid error'. $this->_db->getLastError());
            return FALSE;

        }

    }


}
