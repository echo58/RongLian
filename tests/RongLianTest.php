<?php

namespace Huying\RongLian\Test;

use GuzzleHttp\TransferStats;
use Huying\RongLian\RongLian;
use PHPUnit\Framework\TestCase;

class RongLianTest extends TestCase
{
    /**
     * @var \Huying\RongLian\RongLian
     */
    protected $rongLian;

    protected function setUp()
    {
        $rongLian = new RongLian(ACCOUNT_SID, AUTH_TOKEN, APP_ID);
        $this->rongLian = $rongLian;
    }

    public function testHttpClient()
    {
        $rongLian = new RongLian('accountSid', 'authToken', 'appId');
        $httpClient = $rongLian->getHttpClient();

        $this->assertInstanceOf('GuzzleHttp\Client', $httpClient);

        $httpClient->request('GET', 'AccountInfo', [
            'on_stats' => function (TransferStats $stats) use ($rongLian) {
                $request = $stats->getRequest();
                $uri = $request->getUri();
                $authorizationHeader = $request->getHeaderLine('Authorization');
                $this->assertEquals('https://app.cloopen.com:8883/2013-12-26/Accounts/accountSid/AccountInfo?sig=' . $rongLian->getSignature(), (string)$uri);
                $this->assertEquals($rongLian->getAuthorization(), $authorizationHeader);
            }
        ]);
    }

    public function testGetAccountInfo()
    {
        $accountInfo = $this->rongLian->getAccountInfo();

        $this->assertArrayHasKey('statusCode', $accountInfo);
        $this->assertEquals('000000', $accountInfo['statusCode']);
    }

    public function testVoiceVerify()
    {
        $result = $this->rongLian->voiceVerify(TELEPHONE, time() % 10000);

        $this->assertArrayHasKey('statusCode', $result);
        $this->assertEquals('000000', $result['statusCode']);
    }

    public function testSms()
    {
        $result = $this->rongLian->sms(SMS_TEMPLATE_ID, TELEPHONE, [time() % 10000]);

        $this->assertArrayHasKey('statusCode', $result);
        $this->assertEquals('000000', $result['statusCode']);
    }

    public function testGetFlowPackages()
    {
        $packageInfo = $this->rongLian->getFlowPackages(TELEPHONE);

        $this->assertArrayHasKey('statusCode', $packageInfo);
        $this->assertEquals('000000', $packageInfo['statusCode']);
        $this->assertArrayHasKey('packageList', $packageInfo);
        $packageList = $packageInfo['packageList'];
        $this->assertArrayHasKey('flowPackage', $packageList);
        $flowPackages = $packageList['flowPackage'];
        $this->assertNotEmpty($flowPackages);

        return $flowPackages;
    }

    /**
     * @depends testGetFlowPackages
     */
    public function testRechargeFlow($flowPackages)
    {
        usort($flowPackages, function ($a, $b) {
            return (int)$a['packet'] > (int)$b['packet'];
        });

        $flowPackage = $flowPackages[0];
        $result = $this->rongLian->rechargeFlow(TELEPHONE, $flowPackage['sn'], $flowPackage['packet'], time(), 'http://www.echo58.com');
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertEquals('000000', $result['statusCode']);
        $this->assertArrayHasKey('rechargeId', $result);
        $this->assertArrayHasKey('customId', $result);

        return $result;
    }

    /**
     * @depends testRechargeFlow
     */
    public function testGetFlowRechargeStatus($rechargeResult)
    {
        $result = $this->rongLian->getFlowRechargeStatus($rechargeResult['rechargeId']);
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertEquals('000000', $result['statusCode']);

        $result = $this->rongLian->getFlowRechargeStatus(null, $rechargeResult['customId']);
        $this->assertArrayHasKey('statusCode', $result);
        $this->assertEquals('000000', $result['statusCode']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetFlowRechargeStatusWithoutArgument()
    {
        $this->rongLian->getFlowRechargeStatus();
    }
}
