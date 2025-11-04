<?php

/**
 * Scrutiny
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny\Renderer;

use Closure;
use DecodeLabs\Scrutiny\Renderer;
use DecodeLabs\Scrutiny\Verifier;
use DecodeLabs\Tagged\Element;

class Custom implements Renderer
{
    /**
     * @var Closure(Verifier):Element
     */
    protected Closure $renderer;

    /**
     * @param Closure(Verifier):Element $renderer
     */
    public function __construct(
        Closure $renderer
    ) {
        $this->renderer = $renderer;
    }

    public function render(
        Verifier $verifier
    ): Element {
        return ($this->renderer)($verifier);
    }
}
