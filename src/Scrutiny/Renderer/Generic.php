<?php

/**
 * Scrutiny
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny\Renderer;

use DecodeLabs\Dictum;
use DecodeLabs\Scrutiny\Renderer;
use DecodeLabs\Scrutiny\Verifier;
use DecodeLabs\Tagged\Element;
use ReflectionClass;

class Generic implements Renderer
{
    public function render(
        Verifier $verifier
    ): Element {
        $name = strtolower(
            new ReflectionClass($verifier)
                ->getShortName()
        );

        $attributes = [];

        foreach ($verifier->componentData as $key => $value) {
            $attributes[Dictum::slug($key)] = $value;
        }

        return Element::create('scrutiny-' . $name, null, $attributes);
    }
}
