<?php

declare(strict_types=1);

namespace KCFinder\Exception;

use RuntimeException;

final class AuthorizationDenied extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The requested file operation is not authorized.');
    }
}
