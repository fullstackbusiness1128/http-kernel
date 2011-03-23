<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Exception;

/**
 * NotFoundHttpException.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class NotFoundHttpException extends HttpException
{
    /**
     * Constructor.
     *
     * WARNING: The status message will be sent as a response header
     * regardless of debug mode.
     *
     * @param string    $statusMessage The HTTP response status message
     * @param string    $message       The internal exception message
     * @param integer   $code          The internal exception code
     * @param Exception $previous      The previous exception
     */
    public function __construct($statusMessage = 'Not Found', $message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct(404, $statusMessage, array(), $message ?: $statusMessage, $code, $previous);
    }
}
