<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Debug;

trigger_error('Symfony\Component\HttpKernel\Debug\ErrorHandler is deprecated since version 2.3 and will be removed in 3.0. Use the same class from the Debug component instead.', E_USER_DEPRECATED);

use Symfony\Component\Debug\ErrorHandler as DebugErrorHandler;

/**
 * ErrorHandler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated Deprecated in 2.3, to be removed in 3.0. Use the same class from the Debug component instead.
 */
class ErrorHandler extends DebugErrorHandler
{
}
