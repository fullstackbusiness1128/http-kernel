<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\CacheAttributeListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Tests\Fixtures\Controller\CacheAttributeController;

class CacheAttributeListenerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->listener = new CacheAttributeListener();
        $this->response = new Response();
        $this->cache = new Cache();
        $this->request = $this->createRequest($this->cache);
        $this->event = $this->createEventMock($this->request, $this->response);
    }

    public function testWontReassignResponseWhenResponseIsUnsuccessful()
    {
        $response = $this->event->getResponse();

        $this->response->setStatusCode(500);

        $this->listener->onKernelResponse($this->event);

        $this->assertSame($response, $this->event->getResponse());
    }

    public function testWontReassignResponseWhenNoConfigurationIsPresent()
    {
        $response = $this->event->getResponse();

        $this->request->attributes->remove('_cache');

        $this->listener->onKernelResponse($this->event);

        $this->assertSame($response, $this->event->getResponse());
    }

    public function testResponseIsPublicIfSharedMaxAgeSetAndPublicNotOverridden()
    {
        $request = $this->createRequest(new Cache(smaxage: 1));

        $this->listener->onKernelResponse($this->createEventMock($request, $this->response));

        $this->assertTrue($this->response->headers->hasCacheControlDirective('public'));
        $this->assertFalse($this->response->headers->hasCacheControlDirective('private'));
    }

    public function testResponseIsPublicIfConfigurationIsPublicTrue()
    {
        $request = $this->createRequest(new Cache(public: true));

        $this->listener->onKernelResponse($this->createEventMock($request, $this->response));

        $this->assertTrue($this->response->headers->hasCacheControlDirective('public'));
        $this->assertFalse($this->response->headers->hasCacheControlDirective('private'));
    }

    public function testResponseIsPrivateIfConfigurationIsPublicFalse()
    {
        $request = $this->createRequest(new Cache(public: false));

        $this->listener->onKernelResponse($this->createEventMock($request, $this->response));

        $this->assertFalse($this->response->headers->hasCacheControlDirective('public'));
        $this->assertTrue($this->response->headers->hasCacheControlDirective('private'));
    }

    public function testResponseVary()
    {
        $vary = ['foobar'];
        $request = $this->createRequest(new Cache(vary: $vary));

        $this->listener->onKernelResponse($this->createEventMock($request, $this->response));
        $this->assertTrue($this->response->hasVary());
        $result = $this->response->getVary();
        $this->assertSame($vary, $result);
    }

    public function testResponseVaryWhenVaryNotSet()
    {
        $request = $this->createRequest(new Cache());
        $vary = ['foobar'];
        $this->response->setVary($vary);

        $this->listener->onKernelResponse($this->createEventMock($request, $this->response));
        $this->assertTrue($this->response->hasVary());
        $result = $this->response->getVary();
        $this->assertNotEmpty($result, 'Existing vary headers should not be removed');
        $this->assertSame($vary, $result, 'Vary header should not be changed');
    }

    public function testResponseIsPrivateIfConfigurationIsPublicNotSet()
    {
        $request = $this->createRequest(new Cache());

        $this->listener->onKernelResponse($this->createEventMock($request, $this->response));

        $this->assertFalse($this->response->headers->hasCacheControlDirective('public'));
    }

    public function testAttributeConfigurationsAreSetOnResponse()
    {
        $this->assertNull($this->response->getMaxAge());
        $this->assertNull($this->response->getExpires());
        $this->assertFalse($this->response->headers->hasCacheControlDirective('s-maxage'));
        $this->assertFalse($this->response->headers->hasCacheControlDirective('max-stale'));
        $this->assertFalse($this->response->headers->hasCacheControlDirective('stale-while-revalidate'));
        $this->assertFalse($this->response->headers->hasCacheControlDirective('stale-if-error'));

        $this->request->attributes->set('_cache', [new Cache(
            expires: 'tomorrow',
            maxage: '15',
            smaxage: '15',
            maxStale: '5',
            staleWhileRevalidate: '6',
            staleIfError: '7',
        )]);

        $this->listener->onKernelResponse($this->event);

        $this->assertSame(15, $this->response->getMaxAge());
        $this->assertSame('15', $this->response->headers->getCacheControlDirective('s-maxage'));
        $this->assertSame('5', $this->response->headers->getCacheControlDirective('max-stale'));
        $this->assertSame('6', $this->response->headers->getCacheControlDirective('stale-while-revalidate'));
        $this->assertSame('7', $this->response->headers->getCacheControlDirective('stale-if-error'));
        $this->assertInstanceOf(\DateTime::class, $this->response->getExpires());
    }

    public function testCacheMaxAgeSupportsStrtotimeFormat()
    {
        $this->request->attributes->set('_cache', [new Cache(
            maxage: '1 day',
            smaxage: '1 day',
            maxStale: '1 day',
            staleWhileRevalidate: '1 day',
            staleIfError: '1 day',
        )]);

        $this->listener->onKernelResponse($this->event);

        $this->assertSame('86400', $this->response->headers->getCacheControlDirective('s-maxage'));
        $this->assertSame(86400, $this->response->getMaxAge());
        $this->assertSame('86400', $this->response->headers->getCacheControlDirective('max-stale'));
        $this->assertSame('86400', $this->response->headers->getCacheControlDirective('stale-if-error'));
    }

    public function testLastModifiedNotModifiedResponse()
    {
        $request = $this->createRequest(new Cache(lastModified: 'test.getDate()'));
        $request->attributes->set('test', new TestEntity());
        $request->headers->add(['If-Modified-Since' => 'Fri, 23 Aug 2013 00:00:00 GMT']);

        $listener = new CacheAttributeListener();
        $controllerEvent = new ControllerEvent($this->getKernel(), function () {
            return new Response();
        }, $request, null);

        $listener->onKernelController($controllerEvent);
        $response = \call_user_func($controllerEvent->getController());

        $this->assertSame(304, $response->getStatusCode());
    }

    public function testLastModifiedHeader()
    {
        $request = $this->createRequest(new Cache(lastModified: 'test.getDate()'));
        $request->attributes->set('test', new TestEntity());

        $listener = new CacheAttributeListener();
        $controllerEvent = new ControllerEvent($this->getKernel(), function () {
            return new Response();
        }, $request, null);
        $listener->onKernelController($controllerEvent);

        $responseEvent = new ResponseEvent($this->getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, \call_user_func($controllerEvent->getController()));
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Last-Modified'));
        $this->assertSame('Fri, 23 Aug 2013 00:00:00 GMT', $response->headers->get('Last-Modified'));
    }

    public function testEtagNotModifiedResponse()
    {
        $request = $this->createRequest(new Cache(etag: 'test.getId()'));
        $request->attributes->set('test', $entity = new TestEntity());
        $request->headers->add(['If-None-Match' => sprintf('"%s"', hash('sha256', $entity->getId()))]);

        $listener = new CacheAttributeListener();
        $controllerEvent = new ControllerEvent($this->getKernel(), function () {
            return new Response();
        }, $request, null);

        $listener->onKernelController($controllerEvent);
        $response = \call_user_func($controllerEvent->getController());

        $this->assertSame(304, $response->getStatusCode());
    }

    public function testEtagHeader()
    {
        $request = $this->createRequest(new Cache(etag: 'test.getId()'));
        $request->attributes->set('test', $entity = new TestEntity());

        $listener = new CacheAttributeListener();
        $controllerEvent = new ControllerEvent($this->getKernel(), function () {
            return new Response();
        }, $request, null);
        $listener->onKernelController($controllerEvent);

        $responseEvent = new ResponseEvent($this->getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, \call_user_func($controllerEvent->getController()));
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Etag'));
        $this->assertStringContainsString(hash('sha256', $entity->getId()), $response->headers->get('Etag'));
    }

    public function testConfigurationDoesNotOverrideAlreadySetResponseHeaders()
    {
        $request = $this->createRequest(new Cache(
            expires: 'Fri, 24 Aug 2013 00:00:00 GMT',
            maxage: '15',
            smaxage: '15',
            vary: ['foobar'],
            lastModified: 'Fri, 24 Aug 2013 00:00:00 GMT',
            etag: '"12345"',
        ));

        $response = new Response();
        $response->setEtag('"54321"');
        $response->setLastModified(new \DateTime('Fri, 23 Aug 2014 00:00:00 GMT'));
        $response->setExpires(new \DateTime('Fri, 24 Aug 2014 00:00:00 GMT'));
        $response->setSharedMaxAge(30);
        $response->setMaxAge(30);
        $response->setVary(['foobaz']);

        $listener = new CacheAttributeListener();
        $responseEvent = new ResponseEvent($this->getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $listener->onKernelResponse($responseEvent);

        $this->assertSame('"54321"', $response->getEtag());
        $this->assertEquals(new \DateTime('Fri, 23 Aug 2014 00:00:00 GMT'), $response->getLastModified());
        $this->assertEquals(new \DateTime('Fri, 24 Aug 2014 00:00:00 GMT'), $response->getExpires());
        $this->assertSame('30', $response->headers->getCacheControlDirective('s-maxage'));
        $this->assertSame(30, $response->getMaxAge());
        $this->assertSame(['foobaz'], $response->getVary());
    }

    public function testAttribute()
    {
        $request = new Request();
        $event = new ControllerEvent($this->getKernel(), [new CacheAttributeController(), 'foo'], $request, null);
        $this->listener->onKernelController($event);

        $response = new Response();
        $event = new ResponseEvent($this->getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $this->listener->onKernelResponse($event);

        $this->assertSame(CacheAttributeController::METHOD_SMAXAGE, $response->getMaxAge());

        $request = new Request();
        $event = new ControllerEvent($this->getKernel(), [new CacheAttributeController(), 'bar'], $request, null);
        $this->listener->onKernelController($event);

        $response = new Response();
        $event = new ResponseEvent($this->getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $this->listener->onKernelResponse($event);

        $this->assertSame(CacheAttributeController::CLASS_SMAXAGE, $response->getMaxAge());
    }

    private function createRequest(Cache $cache = null)
    {
        return new Request([], [], ['_cache' => [$cache]]);
    }

    private function createEventMock(Request $request, Response $response)
    {
        return new ResponseEvent($this->getKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }

    private function getKernel(): MockObject&HttpKernelInterface
    {
        return $this->getMockBuilder(HttpKernelInterface::class)->getMock();
    }
}

class TestEntity
{
    public function getDate()
    {
        return new \DateTime('Fri, 23 Aug 2013 00:00:00 GMT');
    }

    public function getId()
    {
        return '12345';
    }
}
