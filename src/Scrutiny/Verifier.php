<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DecodeLabs\Tagged\Component\Scrutiny as ScrutinyComponent;

interface Verifier
{
    public string $name { get; }

    /**
     * @var list<string>
     */
    public array $dataKeys { get; }

    /**
     * @var array<string,mixed>
     */
    public array $componentData { get; }

    public function prepareAssets(
        ScrutinyComponent $component
    ): void;

    public function verify(
        Payload $payload
    ): Result;
}
