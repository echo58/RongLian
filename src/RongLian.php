<?php
namespace Huying\RongLian;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

class RongLian
{
    /**
     * @var string
     */
    protected $accountSid;

    /**
     * @var string
     */
    protected $authToken;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var string yyyyMMddHHmmss格式的当前时间
     */
    protected $time;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * RongLian constructor.
     * @param $accountSid
     * @param $authToken
     * @param $appId
     * @param \GuzzleHttp\Client|null $httpClient
     */
    public function __construct($accountSid, $authToken, $appId, HttpClient $httpClient = null)
    {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
        $this->appId = $appId;
        if (!$httpClient) {
            $this->httpClient = $this->createHttpClient();
        } else {
            $this->httpClient = $httpClient;
        }
    }

    /**
     * 创建 Http Client
     * @return \GuzzleHttp\Client
     */
    public function createHttpClient()
    {
        return new HttpClient([
            'base_uri' => 'https://app.cloopen.com:8883/2013-12-26/Accounts/'.$this->accountSid.'/',
            'headers' => [
                'Accept' => 'application/json;',
                'Content-Type' => 'application/json;charset=utf-8;',
                'Authorization' => $this->getAuthorization(),
            ],
            'query' => [
                'sig' => $this->getSignature(),
            ],
        ]);
    }

    /**
     * 获取当前使用的 Http Client
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * 获取 Authorization
     * @return string
     */
    public function getAuthorization()
    {
        return base64_encode($this->accountSid.':'.$this->getTimestamp());
    }

    /**
     * 获取 sig
     * @return string
     */
    public function getSignature()
    {
        return md5($this->accountSid.$this->authToken.$this->getTimestamp());
    }

    /**
     * 获取当前时间戳
     * @return bool|string
     */
    public function getTimestamp()
    {
        if ($this->time) {
            return $this->time;
        } else {
            return $this->time = date('YmdHis');
        }
    }

    /**
     * 发送 GET 请求
     * @param $uri
     * @param array $query
     * @param array $options
     * @return array
     */
    public function get($uri, array $query = [], array $options = [])
    {
        if (!empty($query)) {
            $options['query'] = $query;
        }

        $response = $this->httpClient->get($uri, $options);
        $this->setResponse($response);

        return \GuzzleHttp\json_decode($response->getBody(), true);
    }

    /**
     * 发送 POST 请求
     * @param $uri
     * @param array $json
     * @param array $options
     * @return array
     */
    public function post($uri, array $json = [], array $options = [])
    {
        if (!empty($json)) {
            $options['json'] = $json;
        }

        $response = $this->httpClient->post($uri, $options);
        $this->response = $response;

        return \GuzzleHttp\json_decode($response->getBody(), true);
    }

    /**
     * 保存服务器的返回
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * 获取服务器的返回
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * 获取容联账户信息
     * @return array
     */
    public function getAccountInfo()
    {
        return $this->get('AccountInfo');
    }

    /**
     * 发送语音验证码
     * @param $to
     * @param $verifyCode
     * @param array $params
     * @return array
     */
    public function voiceVerify($to, $verifyCode, array $params = [])
    {
        $params = array_merge($params, [
            'appId' => $this->appId,
            'verifyCode' => $verifyCode,
            'to' => $to,
        ]);

        return $this->post('Calls/VoiceVerify', $params);
    }

    /**
     * 发送短信验证码
     * @param $templateId
     * @param $to
     * @param $data
     * @return array
     */
    public function sms($templateId, $to, $data)
    {
        return $this->post('SMS/TemplateSMS', [
            'appId' => $this->appId,
            'to' => $to,
            'templateId' => $templateId,
            'datas' => $data,
        ]);
    }

    /**
     * 获取流量包列表
     * @param $telephone
     * @return array
     */
    public function getFlowPackages($telephone)
    {
        return $this->post('flowPackage/flowPackage', [
            'phoneNum' => $telephone,
        ]);
    }

    /**
     * 充流量
     * @param $telephone
     * @param $sn
     * @param $packet
     * @param $customId
     * @param $callbackUrl
     * @param null $reason
     * @return array
     */
    public function rechargeFlow($telephone, $sn, $packet, $customId, $callbackUrl, $reason = null)
    {
        return $this->post('flowPackage/flowRecharge', [
            'appId' => $this->appId,
            'phoneNum' => $telephone,
            'sn' => $sn,
            'packet' => $packet,
            'reason' => $reason,
            'customId' => $customId,
            'callbackUrl' => $callbackUrl,
        ]);
    }

    /**
     * 获取流量充值状态
     * @param null $rechargeId
     * @param null $customId
     * @return array
     */
    public function getFlowRechargeStatus($rechargeId = null, $customId = null)
    {
        if ($rechargeId === null and $customId === null) {
            throw new \InvalidArgumentException('rechargeId 与 customId 不能同时为空');
        }

        $params = [
            'appId' => $this->appId,
        ];
        if ($rechargeId !== null) {
            $params['rechargeId'] = $rechargeId;
        } else {
            $params['customId'] = $customId;
        }

        return $this->post('flowPackage/flowRechargeStatus', $params);
    }
}