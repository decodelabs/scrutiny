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
    public function getName(): string;

    /**
     * @return array<string>
     */
    public function getDataKeys(): array;

    public function getInlineViewAssets(
        ?string $nonce = null
    ): ViewAssetContainer;

    /**
     * @return array<string, mixed>
     */
    public function getComponentData(): array;

    public function verify(
        Payload $payload
    ): Result;
}
