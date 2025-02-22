<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DashboardBundle\Controller\DashboardController;
use Mautic\DashboardBundle\Dashboard\Widget;
use Mautic\DashboardBundle\Model\DashboardModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class DashboardControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Request
     */
    private $requestMock;

    /**
     * @var MockObject|CorePermissions
     */
    private $securityMock;

    /**
     * @var MockObject|Translator
     */
    private $translatorMock;

    /**
     * @var MockObject|ModelFactory<DashboardModel>
     */
    private $modelFactoryMock;

    /**
     * @var MockObject|DashboardModel
     */
    private $dashboardModelMock;

    /**
     * @var MockObject|RouterInterface
     */
    private $routerMock;

    /**
     * @var MockObject|Session
     */
    private $sessionMock;

    /**
     * @var MockObject|FlashBag
     */
    private $flashBagMock;

    /**
     * @var MockObject|Container
     */
    private $containerMock;

    /**
     * @var DashboardController
     */
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestMock        = $this->createMock(Request::class);
        $this->securityMock       = $this->createMock(CorePermissions::class);
        $this->translatorMock     = $this->createMock(Translator::class);
        $this->modelFactoryMock   = $this->createMock(ModelFactory::class);
        $this->dashboardModelMock = $this->createMock(DashboardModel::class);
        $this->routerMock         = $this->createMock(RouterInterface::class);
        $this->sessionMock        = $this->createMock(Session::class);
        $this->flashBagMock       = $this->createMock(FlashBag::class);
        $this->containerMock      = $this->createMock(Container::class);
        $this->controller         = new DashboardController($this->securityMock, $this->createMock(UserHelper::class), $this->createMock(ManagerRegistry::class));
        $requestStack             = new RequestStack();
        $requestStack->push($this->requestMock);
        $this->controller->setRequestStack($requestStack);
        $this->controller->setContainer($this->containerMock);
        $this->controller->setTranslator($this->translatorMock);
        $this->controller->setFlashBag($this->flashBagMock);
        $this->sessionMock->method('getFlashBag')->willReturn($this->flashBagMock);
    }

    public function testSaveWithGetWillCallAccessDenied(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn(Request::METHOD_POST)
            ->willReturn(true);

        $this->requestMock->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->controller->setSecurity($this->securityMock);

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction($this->requestMock);
    }

    public function testSaveWithPostNotAjaxWillCallAccessDenied(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn('POST')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->controller->setSecurity($this->securityMock);

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.core.url.error.401');

        $this->expectException(AccessDeniedHttpException::class);
        $this->controller->saveAction($this->requestMock);
    }

    public function testSaveWithPostAjaxWillSave(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn('POST')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->requestMock->method('get')
            ->withConsecutive(['name'])
            ->willReturnOnConsecutiveCalls('mockName');

        $this->containerMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['router'],
                ['router']
            )
            ->willReturnOnConsecutiveCalls(
                $this->routerMock,
                $this->routerMock
            );

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->modelFactoryMock->expects($this->once())
            ->method('getModel')
            ->with('dashboard')
            ->willReturn($this->dashboardModelMock);

        $this->dashboardModelMock->expects($this->once())
            ->method('saveSnapshot')
            ->with('mockName');

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.dashboard.notice.save');

        // This exception is thrown if twig is not set. Let's take it as success to avoid further mocking.
        $this->expectException(\LogicException::class);
        $this->controller->setModelFactory($this->modelFactoryMock);
        $this->controller->saveAction($this->requestMock);
    }

    public function testSaveWithPostAjaxWillNotBeAbleToSave(): void
    {
        $this->requestMock->expects($this->once())
            ->method('isMethod')
            ->willReturn('POST')
            ->willReturn(true);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->routerMock->expects($this->any())
            ->method('generate')
            ->willReturn('https://some.url');

        $this->requestMock->method('get')
            ->withConsecutive(['name'])
            ->willReturnOnConsecutiveCalls('mockName');

        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('router')
            ->willReturn($this->routerMock);

        $this->modelFactoryMock->expects($this->once())
            ->method('getModel')
            ->with('dashboard')
            ->willReturn($this->dashboardModelMock);

        $this->dashboardModelMock->expects($this->once())
            ->method('saveSnapshot')
            ->will($this->throwException(new IOException('some error message')));

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.dashboard.error.save');

        // This exception is thrown if twig is not set. Let's take it as success to avoid further mocking.
        $this->expectException(\LogicException::class);
        $this->controller->setModelFactory($this->modelFactoryMock);
        $this->controller->saveAction($this->requestMock);
    }

    public function testWidgetDirectRequest(): void
    {
        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->expectException(NotFoundHttpException::class);
        $this->controller->widgetAction($this->requestMock, $this->createMock(Widget::class), 1);
    }

    public function testWidgetNotFound(): void
    {
        $widgetId = '1';

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $widgetService = $this->createMock(Widget::class);
        $widgetService->expects(self::once())
            ->method('setFilter')
            ->with($this->requestMock);
        $widgetService->expects(self::once())
            ->method('get')
            ->with((int) $widgetId)
            ->willReturn(null);

        $this->containerMock->expects(self::never())
            ->method('get');

        $this->expectException(NotFoundHttpException::class);
        $this->controller->widgetAction($this->requestMock, $widgetService, $widgetId);
    }

    public function testWidget(): void
    {
        $widgetId        = '1';
        $widget          = new \Mautic\DashboardBundle\Entity\Widget();
        $renderedContent = 'lfsadkdhfůasfjds';
        $twig            = $this->createMock(Environment::class);

        $twig->expects(self::once())
            ->method('render')
            ->willReturn($renderedContent);

        $this->requestMock->method('isXmlHttpRequest')
            ->willReturn(true);

        $widgetService = $this->createMock(Widget::class);
        $widgetService->expects(self::once())
            ->method('setFilter')
            ->with($this->requestMock);
        $widgetService->expects(self::once())
            ->method('get')
            ->with((int) $widgetId)
            ->willReturn($widget);

        $this->containerMock->expects(self::once())
            ->method('get')
            ->with('twig')
            ->willReturn($twig);

        $response = $this->controller->widgetAction($this->requestMock, $widgetService, $widgetId);

        self::assertSame('{"success":1,"widgetId":"1","widgetHtml":"lfsadkdhf\u016fasfjds","widgetWidth":null,"widgetHeight":null}', $response->getContent());
    }
}
