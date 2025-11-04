<?php

/**
 * Scrutiny
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DecodeLabs\Tagged\Element;

interface Renderer
{
    public function render(
        Verifier $verifier
    ): Element;
}
