<?php

/**
 * @package Scrutiny
 * @license http://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace DecodeLabs\Scrutiny;

class Result
{
    protected Payload $payload;
    protected ?Response $response = null;

    /**
     * @var array<Error>
     */
    protected array $errors = [];

    /**
     * Init with payload
     *
     * @param array<Error> $errors
     */
    public function __construct(
        Payload $payload,
        ?Response $response = null,
        array $errors = []
    ) {
        $this->payload = $payload;
        $this->response = $response;
        $this->errors = $errors;

        // Host name
        if (false === $this->payload->validateHostName(
            $this->response?->getHostName()
        )) {
            $this->errors[] = Error::HostNameMismatch;
        }

        // Action
        if (false === $this->payload->validateAction(
            $this->response?->getAction()
        )) {
            $this->errors[] = Error::ActionMismatch;
        }

        // Threshold
        if (false === $this->payload->validateScoreThreshold(
            $this->response?->getScore()
        )) {
            $this->errors[] = Error::RiskThresholdExceeded;
        }

        // Timeout
        if (false === $this->payload->validateTimeout(
            $this->response?->getTimestamp()
        )) {
            $this->errors[] = Error::Timeout;
        }

        $this->errors = array_unique($this->errors);
    }

    /**
     * Get payload
     */
    public function getPayload(): Payload
    {
        return $this->payload;
    }

    /**
     * Get response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Is valid
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get errors
     *
     * @return array<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
