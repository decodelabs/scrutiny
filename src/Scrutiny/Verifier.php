<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DecodeLabs\Tagged\ViewAssetContainer;

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

    public function prepareInlineViewAssets(
        ?string $nonce = null
    ): ViewAssetContainer;

    public function verify(
        Payload $payload
    ): Result;
}
