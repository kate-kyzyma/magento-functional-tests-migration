<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Security\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Security\Model\SecurityCookie;

/**
 * Test class for \Magento\Security\Model\Plugin\AuthSession testing
 */
class AuthSessionTest extends \PHPUnit\Framework\TestCase
{
    /** @var  \Magento\Security\Model\Plugin\AuthSession */
    protected $model;

    /** @var \Magento\Framework\App\RequestInterface */
    protected $requestMock;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManagerMock;

    /** @var \Magento\Security\Model\AdminSessionsManager */
    protected $adminSessionsManagerMock;

    /** @var SecurityCookie */
    protected $securityCookieMock;

    /** @var \Magento\Backend\Model\Auth\Session */
    protected $authSessionMock;

    /** @var \Magento\Security\Model\AdminSessionInfo */
    protected $currentSessionMock;

    /** @var  \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    protected $objectManager;

    /**@var \Magento\Security\Model\UserExpirationManager */
    protected $userExpirationManagerMock;

    /**@var \Magento\User\Model\User */
    protected $userMock;

    /**
     * Init mocks for tests
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->requestMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            ['getParam', 'getModuleName', 'getActionName'],
            '',
            false
        );

        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);

        $this->adminSessionsManagerMock = $this->createPartialMock(
            \Magento\Security\Model\AdminSessionsManager::class,
            ['getCurrentSession', 'processProlong', 'getLogoutReasonMessage']
        );

        $this->securityCookieMock = $this->createPartialMock(SecurityCookie::class, ['setLogoutReasonCookie']);

        $this->authSessionMock = $this->createPartialMock(
            \Magento\Backend\Model\Auth\Session::class,
            ['destroy', 'getUser']
        );

        $this->currentSessionMock = $this->createPartialMock(
            \Magento\Security\Model\AdminSessionInfo::class,
            ['isLoggedInStatus', 'getStatus', 'isActive']
        );

        $this->userExpirationManagerMock = $this->createPartialMock(
            \Magento\Security\Model\UserExpirationManager::class,
            ['isUserExpired', 'deactivateExpiredUsersById']
        );

        $this->userMock = $this->createMock(\Magento\User\Model\User::class);

        $this->model = $this->objectManager->getObject(
            \Magento\Security\Model\Plugin\AuthSession::class,
            [
                'request' => $this->requestMock,
                'messageManager' => $this->messageManagerMock,
                'sessionsManager' => $this->adminSessionsManagerMock,
                'securityCookie' => $this->securityCookieMock,
                'userExpirationManager' => $this->userExpirationManagerMock,
            ]
        );

        $this->adminSessionsManagerMock->expects($this->any())
            ->method('getCurrentSession')
            ->willReturn($this->currentSessionMock);
    }

    /**
     * @return void
     */
    public function testAroundProlongSessionIsNotActiveAndIsNotAjaxRequest()
    {
        $result = 'result';
        $errorMessage = 'Error Message';

        $proceed = function () use ($result) {
            return $result;
        };

        $this->currentSessionMock->expects($this->once())
            ->method('isLoggedInStatus')
            ->willReturn(false);

        $this->authSessionMock->expects($this->once())
            ->method('destroy');

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('isAjax')
            ->willReturn(false);

        $this->adminSessionsManagerMock->expects($this->once())
            ->method('getLogoutReasonMessage')
            ->willReturn($errorMessage);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($errorMessage);

        $this->model->aroundProlong($this->authSessionMock, $proceed);
    }

    /**
     * @return void
     */
    public function testAroundProlongSessionIsNotActiveAndIsAjaxRequest()
    {
        $result = 'result';
        $status = 1;

        $proceed = function () use ($result) {
            return $result;
        };

        $this->currentSessionMock->expects($this->any())
            ->method('isActive')
            ->willReturn(false);

        $this->authSessionMock->expects($this->once())
            ->method('destroy');

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('isAjax')
            ->willReturn(true);

        $this->currentSessionMock->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $this->securityCookieMock->expects($this->once())
            ->method('setLogoutReasonCookie')
            ->with($status)
            ->willReturnSelf();

        $this->model->aroundProlong($this->authSessionMock, $proceed);
    }

    /**
     * @return void
     */
    public function testAroundProlongSessionIsActiveUserIsExpired()
    {
        $result = 'result';
        $errorMessage = 'Error Message';

        $proceed = function () use ($result) {
            return $result;
        };

        $adminUserId = '12345';
        $this->currentSessionMock->expects($this->once())
            ->method('isLoggedInStatus')
            ->willReturn(true);

        $this->authSessionMock->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($this->userMock);

        $this->userMock->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($adminUserId);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('isAjax')
            ->willReturn(false);

        $this->userExpirationManagerMock->expects($this->once())
            ->method('isUserExpired')
            ->with($adminUserId)
            ->willReturn(true);

        $this->userExpirationManagerMock->expects($this->once())
            ->method('deactivateExpiredUsersById')
            ->with([$adminUserId]);

        $this->authSessionMock->expects($this->once())
            ->method('destroy');

        $this->adminSessionsManagerMock->expects($this->once())
            ->method('getLogoutReasonMessage')
            ->willReturn($errorMessage);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with($errorMessage);

        $this->model->aroundProlong($this->authSessionMock, $proceed);
    }

    /**
     * @return void
     */
    public function testAroundProlongSessionIsActive()
    {
        $result = 'result';
        $proceed = function () use ($result) {
            return $result;
        };

        $adminUserId = '12345';
        $this->currentSessionMock->expects($this->any())
            ->method('isLoggedInStatus')
            ->willReturn(true);

        $this->authSessionMock->expects($this->once())
            ->method('getUser')
            ->willReturn($this->userMock);

        $this->userMock->expects($this->once())
            ->method('getId')
            ->willReturn($adminUserId);

        $this->userExpirationManagerMock->expects($this->once())
            ->method('isUserExpired')
            ->with($adminUserId)
            ->willReturn(false);

        $this->adminSessionsManagerMock->expects($this->any())
            ->method('processProlong');

        $this->assertEquals($result, $this->model->aroundProlong($this->authSessionMock, $proceed));
    }
}
