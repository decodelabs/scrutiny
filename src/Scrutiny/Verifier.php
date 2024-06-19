<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DecodeLabs\Tagged\Markup;

interface Verifier
{
    public function renderInline(
        ?string $nonce = null
    ): Markup;

    /**
     * @return array<string, mixed>
     */
    public function getComponentData(): array;

    public function verify(
        Payload $payload
    ): Result;
}
