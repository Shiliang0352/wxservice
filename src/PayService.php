<?php

namespace saowx;

use saowx\lib\SaoBasic;
use saowx\lib\Clinet;


class PayService extends SaoBasic
{
    protected $appid,$mchId,$mchKey,$notify_url,$mchCert,$mchCertKey,$trade_type;

    public $result;

    protected $url = 'https://api.mch.weixin.qq.com/pay';

    public function __construct($appid,$mchId,$mchKey,$notify_url,$trade_type,$mchCert,$mchCertKey)
    {
        $this->appid = $appid;
        $this->mchId = $mchId;
        $this->mchKey = $mchKey;
        $this->notify_url = $notify_url;
        $this->mchCert = $mchCert;
        $this->mchCertKey = $mchCertKey;
        $this->trade_type = $trade_type;
        $this->result = new \stdClass();
    }

    /**
     * 微信统一下单
     *
     * @param $openid       //  谁的订单
     * @param $body         //  什么东西
     * @param $total_fee    //  多少钱
     * @param $out_trade_no //  订单号
     * @param $m_params     //  可选参数 见微信支付文档
     * @return array|mixed
     */
    public function order($openid,$body,$total_fee,$out_trade_no,array $m_params=array())
    {
        $data = [
            'openid'=>$openid,
            'body'=>$body,
            'total_fee'=>$total_fee,
            'out_trade_no'=>$out_trade_no,
            'notify_url'=>$this->notify_url,
            'appid'=>$this->appid,
            'trade_type'=>$this->trade_type,
            'mch_id'=>$this->mchId,
            'spbill_create_ip'=>'1.1.1.1',
            'nonce_str'=>$this->nonce_str(),
        ];

        $data = array_merge($data,$m_params);
        $data['sign'] = $this->mchSign($data,$this->mchKey);
        $data = $this->arrayToXml($data);

        $url = $this->url.'/unifiedorder';
        $data = [ 'raw' => $data ];

        $res = Clinet::new()->post($url,$data);

        if ($res->E_code == 0) {
            $res->data = $this->xmlToArray($res->data);
        }
        return $res;
    }

    /**
     * 企业付款到零钱
     *
     * @param $openid           //  转给谁
     * @param $amount           //  多少钱
     * @param $desc             //  转款说明
     * @param $partner_trade_no //  订单号
     * @param $m_params         //  可选参数 见微信支付文档
     * @return array|mixed
     */
    public function costToUser($openid,$amount,$desc,$partner_trade_no,array $m_params=array())
    {
        if (is_file($this->mchCert) || is_file($this->mchCertKey)){
            $this->result->E_code = 50042;
            $this->result->E_msg = '证书不存在';
            return $this->result;
        }

        $data = [
            'openid'=>$openid,
            'amount'=>$amount,
            'desc'=>$desc,
            'partner_trade_no'=>$partner_trade_no,
            'mch_appid'=>$this->appid,
            'mchid'=>$this->mchId,
            'nonce_str'=>$this->nonce_str(),
            'check_name'=>'NO_CHECK',
            'spbill_create_ip'=>'1.1.1.1',
        ];
        $data = array_merge($data,$m_params);
        $data['sign'] = $this->mchSign($data,$this->mchKey);
        $data = $this->arrayToXml($data);

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $data = [ 'raw' => $data ];
        $data['pem'] = $this->mchCert;
        $data['pem_key'] = $this->mchCertKey;

        $res = Clinet::new()->post($url,$data);

        if ($res->E_code == 0) {
            $res->data = $this->xmlToArray($res->data);
        }
        return $res;

    }

    /**
     * 发放现金红包
     *
     * @param $openid           //  发给谁
     * @param $amount           //  金额
     * @param $desc             //  红包祝福语
     * @param $send_name        //  谁发的
     * @param $act_name         //  活动名称
     * @param $remark           //  备注
     * @param $mch_billno       //  订单号
     * @param $m_params         //  可选参数 见微信支付文档
     * @param array $m_params
     * @return array|mixed|\stdClass
     */
    public function redpackToUser($openid,$amount,$desc,$send_name,$act_name,$remark,$mch_billno,array
    $m_params=array())
    {
        if (is_file($this->mchCert) || is_file($this->mchCertKey)){
            $this->result->E_code = 50042;
            $this->result->E_msg = '证书不存在';
            return $this->result;
        }

        $data = [
            're_openid'=>$openid,
            'total_amount'=>$amount,
            'wishing'=>$desc,
            'send_name'=>$send_name,
            'act_name'=>$act_name,
            'remark'=>$remark,
            'mch_billno'=>$mch_billno,
            'wxappid'=>$this->appid,
            'mch_id'=>$this->mchId,
            'nonce_str'=>$this->nonce_str(),
            'check_name'=>'NO_CHECK',
            'client_ip'=>'1.1.1.1',
            'total_num'=>1,
        ];
        $data = array_merge($data,$m_params);
        $data['sign'] = $this->mchSign($data,$this->mchKey);
        $data = $this->arrayToXml($data);

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $data = [ 'raw' => $data ];
        $data['pem'] = $this->mchCert;
        $data['pem_key'] = $this->mchCertKey;

        $res = Clinet::new()->post($url,$data);

        if ($res->E_code == 0) {
            $res->data = $this->xmlToArray($res->data);
        }
        return $res;
    }
}