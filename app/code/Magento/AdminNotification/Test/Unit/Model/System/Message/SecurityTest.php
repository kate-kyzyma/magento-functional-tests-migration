<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminNotification\Test\Unit\Model\System\Message;

use Magento\AdminNotification\Model\System\Message\Security;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    /**
     * @var CacheInterface|MockObject
     */
    private $cacheMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var CurlFactory|MockObject
     */
    private $curlFactoryMock;

    /**
     * @var Security
     */
    private $messageModel;

    protected function setUp(): void
    {
        //Prepare objects for constructor
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->curlFactoryMock = $this->createPartialMock(
            CurlFactory::class,
            ['create']
        );

        $objectManagerHelper = new ObjectManager($this);
        $arguments = [
            'cache' => $this->cacheMock,
            'scopeConfig' => $this->scopeConfigMock,
            'curlFactory' => $this->curlFactoryMock,
        ];
        $this->messageModel = $objectManagerHelper->getObject(
            Security::class,
            $arguments
        );
    }

    /**
     *
     * @param $expectedResult
     * @param $cached
     * @param $response
     * @return void
     * @dataProvider isDisplayedDataProvider
     */
    public function testIsDisplayed($expectedResult, $cached, $response)
    {
        $this->cacheMock->expects($this->any())->method('load')->will($this->returnValue($cached));
        $this->cacheMock->expects($this->any())->method('save')->will($this->returnValue(null));

        $httpAdapterMock = $this->createMock(Curl::class);
        $httpAdapterMock->expects($this->any())->method('read')->will($this->returnValue($response));
        $this->curlFactoryMock->expects($this->any())->method('create')->will($this->returnValue($httpAdapterMock));

        $this->scopeConfigMock->expects($this->any())->method('getValue')->will($this->returnValue(null));

        $this->assertEquals($expectedResult, $this->messageModel->isDisplayed());
    }

    /**
     * @return array
     */
    public function isDisplayedDataProvider()
    {
        return [
            'cached_case' => [false, true, ''],
            'accessible_file' => [true, false, 'HTTP/1.1 200'],
            'inaccessible_file' => [false, false, 'HTTP/1.1 403']
        ];
    }

    public function testGetText()
    {
        $messageStart = 'Your web server is set up incorrectly';

        $this->assertStringStartsWith($messageStart, (string)$this->messageModel->getText());
    }
}
