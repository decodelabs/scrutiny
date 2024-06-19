<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

interface Verifier
{
    public function verify(
        Payload $payload
    ): Result;
}
