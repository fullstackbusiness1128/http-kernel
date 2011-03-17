<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel;

/**
 * Contains all events thrown in the HttpKernel component
 *
 * @author Bernhard Schussek <bernhard.schussek@symfony.com>
 */
final class Events
{
    /**
     * The onCoreRequest event occurs at the very beginning of request
     * dispatching
     *
     * This event allows you to create a response for a request before any
     * other code in the framework is executed. The event listener method
     * receives a Symfony\Component\HttpKernel\Event\GetResponseEvent
     * instance.
     *
     * @var string
     */
    const onCoreRequest = 'onCoreRequest';

    /**
     * The onCoreException event occurs when an uncaught exception appears
     *
     * This event allows you to create a response for a thrown exception or
     * to modify the thrown exception. The event listener method receives
     * a Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent
     * instance.
     *
     * @var string
     */
    const onCoreException = 'onCoreException';

    /**
     * The onCoreView event occurs when the return value of a controller
     * is not a Response instance
     *
     * This event allows you to create a response for the return value of the
     * controller. The event listener method receives a
     * Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent
     * instance.
     *
     * @var string
     */
    const onCoreView = 'onCoreView';

    /**
     * The filterCoreController event occurs once a controller was found for
     * handling a request
     *
     * This event allows you to change the controller that will handle the
     * request. The event listener method receives a
     * Symfony\Component\HttpKernel\Event\FilterControllerEvent instance.
     *
     * @var string
     */
    const filterCoreController = 'filterCoreController';

    /**
     * The filterCoreController event occurs once a reponse was created for
     * replying to a request
     *
     * This event allows you to modify or replace the response that will be
     * replied. The event listener method receives a
     * Symfony\Component\HttpKernel\Event\FilterResponseEvent instance.
     *
     * @var string
     */
    const filterCoreResponse = 'filterCoreResponse';
}