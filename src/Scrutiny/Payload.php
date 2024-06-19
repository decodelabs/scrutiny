<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DecodeLabs\Compass\Ip;
use Throwable;

class Payload
{
    protected ?Ip $ip = null;
    protected ?string $verifierName;

    /**
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * @var array<string>
     */
    protected array $hostNames = [];
    protected ?string $action;
    protected ?float $scoreThreshold = null;
    protected ?int $timeout = null;

    /**
     * Init with values
     *
     * @param array<string, mixed> $values
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
        $this->ip = $ip;
        $this->values = $values;

        $this->hostNames = $hostNames;
        $this->action = $action;
        $this->scoreThreshold = $scoreThreshold;
        $this->timeout = $timeout;
    }

    /**
     * Get verifier name
     */
    public function getVerifierName(): ?string
    {
        return $this->verifierName;
    }

    /**
     * Get IP
     */
    public function getIp(): Ip
    {
        if ($this->ip === null) {
            $this->ip = $this->extrapolateIp();
        }

        return $this->ip;
    }

    protected function extrapolateIp(): Ip
    {
        $ips = '';

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips .= $_SERVER['HTTP_X_FORWARDED_FOR'] . ',';
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ips .= $_SERVER['REMOTE_ADDR'] . ',';
        }

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ips .= $_SERVER['HTTP_CLIENT_IP'] . ',';
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
     * Get all values
     *
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->values;
    }



    /**
     * Get host names
     *
     * @return array<string>
     */
    public function getHostNames(): array
    {
        return $this->hostNames;
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
     * Get action
     */
    public function getAction(): string
    {
        return $this->action ?? 'default';
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
     * Set timeout
     */
    public function setTimeout(
        ?int $timeout
    ): void {
        if ($timeout <= 0) {
            $timeout = null;
        }

        $this->timeout = $timeout;
    }

    /**
     * Get timeout
     */
    public function getTimeout(): ?int
    {
        return $this->timeout;
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
     * Set score threshold
     */
    public function setScoreThreshold(
        ?float $scoreThreshold
    ): void {
        $this->scoreThreshold = min(1, max(0, $scoreThreshold));
    }

    /**
     * Get score threshold
     */
    public function getScoreThreshold(): ?float
    {
        return $this->scoreThreshold;
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
