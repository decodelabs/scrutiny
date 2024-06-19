<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

use DateTimeInterface;

class Response
{
    protected ?int $timestamp;
    protected ?string $hostName;
    protected ?string $action;
    protected ?float $score;
    protected int|float|string|null $rawScore;

    public function __construct(
        ?string $hostName = null,
        ?string $action = null,
        int|DateTimeInterface|null $timestamp = null,
        ?float $score = null,
        int|float|string|null $rawScore = null
    ) {
        if ($timestamp instanceof DateTimeInterface) {
            $timestamp = $timestamp->getTimestamp();
        }

        $this->hostName = $hostName;
        $this->action = $action;
        $this->timestamp = $timestamp;
        $this->score = $score;
        $this->rawScore = $rawScore;
    }

    /**
     * Get host name
     */
    public function getHostName(): ?string
    {
        return $this->hostName;
    }

    /**
     * Get action
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * Get timestamp
     */
    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    /**
     * Get score
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * Get raw score
     */
    public function getRawScore(): int|float|string|null
    {
        return $this->rawScore;
    }
}
