<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Dovetail\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Scrutiny\Config as ConfigInterface;

class Scrutiny implements Config, ConfigInterface
{
    use ConfigTrait;

    public static function getDefaultValues(): array
    {
        return [
            'VerifierName' => [
                'enabled' => false,
                'siteKey' => '--siteKey--',
                'secret' => "{{envString('VERIFIER_SECRET')}}",
            ]
        ];
    }

    public function getFirstEnabledVerifier(): ?string
    {
        foreach ($this->data as $name => $settings) {
            if ($settings->enabled->as('bool', [
                'default' => true
            ])) {
                return (string)$name;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettingsFor(
        string $verifierName
    ): array {
        return $this->data->{$verifierName}->toArray();
    }
}
