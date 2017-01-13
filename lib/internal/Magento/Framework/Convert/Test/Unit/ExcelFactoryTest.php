<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Convert\Test\Unit;

use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\ObjectManagerInterface;

class ExcelFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExcelFactory
     */
    protected $model;

    /**
     * @var ObjectManagerInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->model = new ExcelFactory(
            $this->objectManager
        );
    }

    public function testCreate()
    {
        $excel = $this->getMockBuilder(\Magento\Framework\Convert\Excel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->once())
            ->method('create')
            ->with(\Magento\Framework\Convert\Excel::class, [])
            ->willReturn($excel);

        $this->assertInstanceOf(\Magento\Framework\Convert\Excel::class, $this->model->create());
    }
}
