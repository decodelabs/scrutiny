<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DecodeLabs\Coercion;
use DecodeLabs\Compass\Ip;
use Throwable;

class Payload
{
    protected(set) Ip $ip {
        get {
            if (!isset($this->ip)) {
                $this->ip = $this->extrapolateIp();
            }

            return $this->ip;
        }
    }

    protected(set) ?string $verifierName;

    /**
     * @var array<string,mixed>
     */
    protected(set) array $values = [];

    /**
     * @var array<string>
     */
    protected(set) array $hostNames = [];

    protected(set) ?string $action {
        get => $this->action ?? 'default';
    }

    public ?float $scoreThreshold = null {
        set => min(1, max(0, $value));
    }

    public ?int $timeout = null {
        set {
            if ($value <= 0) {
                $value = null;
            }

            $this->timeout = $value;
        }
    }

    /**
     * Init with values
     *
     * @param array<string,mixed> $values
     * @param array<string> $hostNames
     */
    public function __construct(
        ?string $verifierName,
        ?Ip $ip = null,
        array $values = [],
        array $hostNames = [],
        ?string $action = null,
        ?int $timeout = null,
        ?float $scoreThreshold = null,
    ) {
        if ($timeout <= 0) {
            $timeout = null;
        }

        $this->verifierName = $verifierName;

        if($ip !== null) {
            $this->ip = $ip;
        }

        $this->values = $values;

        $this->hostNames = $hostNames;
        $this->action = $action;
        $this->scoreThreshold = $scoreThreshold;
        $this->timeout = $timeout;
    }

    protected function extrapolateIp(): Ip
    {
        $ips = '';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips .= Coercion::asString($_SERVER['HTTP_X_FORWARDED_FOR']) . ',';
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ips .= Coercion::asString($_SERVER['REMOTE_ADDR']) . ',';
        }

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ips .= Coercion::asString($_SERVER['HTTP_CLIENT_IP']) . ',';
        }

        $parts = explode(',', rtrim($ips, ','));

        while (!empty($parts)) {
            $ip = trim(array_shift($parts));

            try {
                return Ip::parse($ip);
            } catch (Throwable $e) {
            }
        }

        return new Ip('0.0.0.0');
    }


    /**
     * Get value
     */
    public function getValue(
        string $name
    ): mixed {
        return $this->values[$name] ?? null;
    }

    /**
     * Has host names
     */
    public function hasHostNames(): bool
    {
        return !empty($this->hostNames);
    }

    /**
     * Has host name
     */
    public function validateHostName(
        ?string $hostName
    ): ?bool {
        if (
            $hostName === null ||
            empty($this->hostNames)
        ) {
            return null;
        }

        return in_array(Context::prepareHostName($hostName), $this->hostNames);
    }

    /**
     * Validate action
     */
    public function validateAction(
        ?string $action
    ): ?bool {
        if (
            $action === null ||
            $this->action === null
        ) {
            return null;
        }

        return $action === $this->action;
    }

    /**
     * Validate timeout
     */
    public function validateTimeout(
        ?int $timestamp
    ): ?bool {
        if (
            $timestamp === null ||
            $this->timeout === null
        ) {
            return null;
        }

        return time() - $timestamp <= $this->timeout;
    }

    /**
     * Validate score threshold
     */
    public function validateScoreThreshold(
        ?float $score
    ): ?bool {
        if (
            $score === null ||
            $this->scoreThreshold === null
        ) {
            return null;
        }

        return $score < $this->scoreThreshold;
    }
}
