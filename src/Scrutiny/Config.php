<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

interface Config
{
    public function getFirstEnabledVerifier(): ?string;

    /**
     * @return array<string,mixed>
     */
    public function getSettingsFor(
        string $verifierName
    ): array;
}
