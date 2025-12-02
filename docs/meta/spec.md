# Scrutiny — Package Specification

> **Cluster:** `logic`
> **Language:** `php`
> **Milestone:** `m5`
> **Repo:** `https://github.com/decodelabs/scrutiny`
> **Role:** Captcha

## Overview

### Purpose

Scrutiny provides a simple, standardized interface for checking your users are human. It supports a range of different verification methods (CAPTCHA services) and can be easily extended to support more.

Key features:
- **Unified interface**: Single API for multiple CAPTCHA providers
- **Extensible**: Easy to add custom verifiers
- **Configurable**: Runtime configuration via Dovetail or custom config
- **HTML integration**: Tagged component for easy HTML rendering
- **Validation**: Built-in validation for hostname, action, score thresholds, and timeouts
- **Compound verifiers**: Support for multiple verifiers in sequence

### Non-Goals

- Scrutiny does not provide CAPTCHA service implementations (relies on external services).
- It does not handle CAPTCHA solving or automation detection beyond service responses.
- It does not provide rate limiting or abuse prevention (handled by verifiers).
- It does not manage CAPTCHA service accounts or API keys (configured externally).
- It does not provide CAPTCHA analytics or reporting dashboards.

## Role in the Ecosystem

### Cluster & Positioning

Scrutiny belongs to the **logic** cluster, focusing on security and validation. It complements other logic packages like `lucid` (validation) and `sanctum` (CSP) by providing human verification capabilities.

### Usage Contexts

- **Form protection**: Protecting forms from automated submissions
- **User registration**: Verifying human users during registration
- **Comment systems**: Preventing spam in comment systems
- **API protection**: Protecting API endpoints from automated abuse
- **Login protection**: Adding CAPTCHA to login forms for additional security

## Public Surface

### Key Types

- **`Scrutiny`** (class): Main service class providing verifier loading, verification, and renderer management. Implements `Service` interface for Kingdom integration.

- **`Verifier`** (interface): Interface for CAPTCHA verification implementations. Defines contract for verification, asset preparation, and component data.

- **`Verifier\SiteVerify`** (abstract class): Base implementation for site verification-based CAPTCHAs (reCAPTCHA, hCaptcha). Handles HTTP verification requests and response parsing.

- **`Verifier\Recaptcha`** (class): Google reCAPTCHA v3 implementation extending `SiteVerify`.

- **`Verifier\HCaptcha`** (class): hCaptcha implementation extending `SiteVerify`.

- **`Verifier\Compound`** (class): Compound verifier that tries multiple verifiers in sequence until one succeeds.

- **`Renderer`** (interface): Interface for rendering verifier HTML components.

- **`Renderer\Generic`** (class): Generic renderer that creates HTML elements based on verifier class name.

- **`Renderer\Custom`** (class): Custom renderer using a closure for rendering.

- **`Payload`** (class): Carries verification request data including IP address, values, hostnames, action, timeout, and score threshold.

- **`Result`** (class): Represents verification result with payload, response, and errors. Provides validation status.

- **`Response`** (class): Represents verification service response with timestamp, hostname, action, and score.

- **`Error`** (enum): Error types that can occur during verification.

- **`Config`** (interface): Configuration interface for verifier settings.

- **`Dovetail\Config\Scrutiny`** (class): Dovetail-based configuration implementation.

- **`Tagged\Component\Scrutiny`** (class): Tagged component for rendering CAPTCHA widgets in HTML.

### Main Entry Points

**Service:**
- `Scrutiny::provideService(ContainerAdapter $container): Scrutiny` — Service provider for Kingdom
- `new Scrutiny(?Config $config = null)` — Constructor
- `$scrutiny->loadVerifier(?string $name = null, ?array $settings = null): Verifier` — Load verifier (throws if not found)
- `$scrutiny->tryLoadVerifier(?string $name = null, ?array $settings = null): ?Verifier` — Load verifier (returns null if not found)
- `$scrutiny->verify(?string $verifierName = null, array $values = [], ?string $action = null, ?float $scoreThreshold = null, ?int $timeout = null): Result` — Verify with values
- `$scrutiny->verifyPayload(Payload $payload): Result` — Verify with payload
- `$scrutiny->createPayload(?string $verifierName = null, array $values = [], ?string $action = null, ?float $scoreThreshold = null, ?int $timeout = null): Payload` — Create payload

**Renderer Management:**
- `$scrutiny->registerRenderer(string $verifierName, Renderer $renderer): void` — Register renderer for verifier
- `$scrutiny->registerCustomRenderer(string $verifierName, Closure $renderer): void` — Register custom closure renderer
- `$scrutiny->registerDefaultRenderer(Renderer $renderer): void` — Register default renderer
- `$scrutiny->getRenderer(string $verifierName): Renderer` — Get renderer for verifier
- `$scrutiny->removeRenderer(string $verifierName): void` — Remove renderer

**Hostname Management:**
- `$scrutiny->addHostNames(string ...$hostNames): void` — Add allowed hostnames
- `$scrutiny->removeHostNames(string ...$hostNames): void` — Remove hostnames
- `$scrutiny->getHostNames(): array` — Get all hostnames
- `Scrutiny::prepareHostName(string $hostName): string` — Normalize hostname

**Verifier Interface:**
- `$verifier->name` — Verifier name (readonly property)
- `$verifier->dataKeys` — Array of data keys required for verification (readonly property)
- `$verifier->componentData` — Array of data for component rendering (readonly property)
- `$verifier->prepareAssets(ScrutinyComponent $component): void` — Prepare HTML assets
- `$verifier->verify(Payload $payload): Result` — Verify payload

**Payload:**
- `new Payload(?string $verifierName, ?Ip $ip = null, array $values = [], array $hostNames = [], ?string $action = null, ?int $timeout = null, ?float $scoreThreshold = null)` — Constructor
- `$payload->ip` — IP address (lazy-loaded from server variables)
- `$payload->verifierName` — Verifier name
- `$payload->values` — Verification values (e.g., CAPTCHA response tokens)
- `$payload->hostNames` — Allowed hostnames
- `$payload->action` — Action name (defaults to 'default')
- `$payload->scoreThreshold` — Score threshold (0-1, clamped)
- `$payload->timeout` — Timeout in seconds
- `$payload->getValue(string $name): mixed` — Get value by name
- `$payload->hasHostNames(): bool` — Check if hostnames are set
- `$payload->validateHostName(?string $hostName): ?bool` — Validate hostname
- `$payload->validateAction(?string $action): ?bool` — Validate action
- `$payload->validateTimeout(?int $timestamp): ?bool` — Validate timeout
- `$payload->validateScoreThreshold(?float $score): ?bool` — Validate score threshold

**Result:**
- `new Result(Payload $payload, ?Response $response = null, array $errors = [])` — Constructor
- `$result->payload` — Verification payload (readonly)
- `$result->response` — Verification response (readonly, nullable)
- `$result->errors` — Array of errors (readonly)
- `$result->isValid(): bool` — Check if verification succeeded

**Response:**
- `new Response(?string $hostName = null, ?string $action = null, int|DateTimeInterface|null $timestamp = null, ?float $score = null, int|float|string|null $rawScore = null)` — Constructor
- `$response->hostName` — Hostname from verification service
- `$response->action` — Action from verification service
- `$response->timestamp` — Timestamp (readonly)
- `$response->score` — Normalized score (0-1, readonly)
- `$response->rawScore` — Raw score from service (readonly)

**Error Enum:**
- `Error::VerifierNotFound` — Verifier not found
- `Error::InvalidSecret` — Invalid secret key
- `Error::InvalidPayload` — Invalid payload data
- `Error::ConnectionFailed` — Connection to service failed
- `Error::VerifierFailed` — Verification failed
- `Error::InvalidInput` — Invalid input data
- `Error::HostNameMismatch` — Hostname mismatch
- `Error::ActionMismatch` — Action mismatch
- `Error::RiskThresholdExceeded` — Score exceeds threshold
- `Error::Timeout` — Verification timeout
- `Error::isReportable(Error $error): bool` — Check if error is reportable

**Tagged Component:**
- `new Tagged\Component\Scrutiny(string|Verifier|null $verifier = null, ?array $settings = null, ?string $nonce = null, ?array $attributes = null)` — Constructor
- `$component->verifier` — Verifier instance (readonly)
- `$component->nonce` — Nonce for CSP (readonly)
- `$component->render(bool $pretty = false): ?Buffer` — Render component
- `$component->renderInline(bool $pretty = false): ?Buffer` — Render with assets inline

## Dependencies

### Decode Labs

- **`decodelabs/archetype`**: Used for verifier class resolution via Slingshot.
- **`decodelabs/coercion`**: Used for type coercion in verifier implementations.
- **`decodelabs/compass`**: Used for IP address parsing and validation.
- **`decodelabs/dictum`**: Used for text formatting (slug generation).
- **`decodelabs/exceptional`**: Used for exception handling throughout the package.
- **`decodelabs/horizon`**: Used for HTML document component properties (script/link collections).
- **`decodelabs/hydro`**: Used for HTTP requests to verification services.
- **`decodelabs/kingdom`**: Used for service container integration.
- **`decodelabs/monarch`**: Used for service location and path management.
- **`decodelabs/nuance`**: Used for `Dumpable` interface support and sensitive property handling.
- **`decodelabs/slingshot`**: Used for dependency injection and verifier instantiation.
- **`decodelabs/tagged`**: Used for HTML component rendering.

### External

- **PHP**: See `composer.json` for supported PHP versions.

### Optional

- **`decodelabs/dovetail`**: Detected at runtime if installed, used for configuration via `Dovetail\Config\Scrutiny`. If available, automatically registered as `Config` implementation in Kingdom service provider.

## Behaviour & Contracts

### Invariants

- A `Payload` instance always has an IP address (defaults to '0.0.0.0' if extraction fails).
- Score thresholds are clamped to 0-1 range.
- Timeout values must be positive (null or > 0).
- Action defaults to 'default' if not specified.
- A `Result` instance validates response against payload constraints during construction.
- Errors are deduplicated in `Result` constructor.

### Input & Output Contracts

**Verifier Loading:**
- `loadVerifier()` throws `NotFound` exception if verifier is not found or disabled.
- `tryLoadVerifier()` returns `null` if verifier is not found or disabled.
- Verifier name defaults to first enabled verifier from config if not specified.
- Settings default to config settings if not provided.

**Verification:**
- `verify()` creates a payload and calls `verifyPayload()`.
- `verifyPayload()` returns `Result` with errors if verifier not found.
- Verifiers return `Result` with `Response` on success, or `Result` with errors on failure.

**IP Address Extraction:**
- IP is extracted from `HTTP_X_FORWARDED_FOR`, `REMOTE_ADDR`, or `HTTP_CLIENT_IP` server variables.
- First valid IP from the list is used.
- Falls back to '0.0.0.0' if no valid IP found.

**Hostname Validation:**
- Hostnames are normalized (protocol and trailing slashes removed).
- Validation returns `null` if no hostnames configured or response has no hostname.
- Returns `true` if hostname matches, `false` otherwise.

**Score Validation:**
- Scores are normalized (reCAPTCHA: 1 - score, hCaptcha: score as-is).
- Validation returns `null` if no threshold configured or response has no score.
- Returns `true` if score is below threshold, `false` otherwise.

**Timeout Validation:**
- Validation returns `null` if no timeout configured or response has no timestamp.
- Returns `true` if verification is within timeout window, `false` otherwise.

## Error Handling

- **Verifier not found**: `loadVerifier()` throws `NotFound` exception.
- **Invalid payload**: Verifiers return `Result` with `InvalidPayload` error if required values are missing.
- **HTTP errors**: Verifiers return `Result` with `VerifierFailed` or `InvalidInput` errors based on HTTP status codes.
- **Service errors**: Service error codes are mapped to appropriate `Error` enum values.
- **Validation failures**: `Result` constructor adds validation errors for hostname, action, score, and timeout mismatches.

## Configuration & Extensibility

### Creating Custom Verifiers

Implement the `Verifier` interface:

```php
class MyVerifier implements Verifier
{
    public string $name { get => 'MyVerifier'; }
    
    public array $dataKeys { get => ['my-response-field']; }
    
    public array $componentData { get => ['siteKey' => $this->siteKey]; }
    
    public function prepareAssets(ScrutinyComponent $component): void
    {
        // Add scripts, styles, etc.
    }
    
    public function verify(Payload $payload): Result
    {
        // Perform verification
        // Return Result with Response or errors
    }
}
```

### Extending SiteVerify

For services using site verification pattern:

```php
class MySiteVerify extends SiteVerify
{
    protected const string VerifyUrl = 'https://api.example.com/verify';
    protected const string ApiUrl = 'https://api.example.com/api.js';
    protected const string ClientKeyName = 'my-captcha';
    protected const string ResponseFieldName = 'my-captcha-response';
    
    public string $name { get => 'MySiteVerify'; }
    
    protected function createResponse(array $data): Response
    {
        // Parse service response
        return new Response(...);
    }
}
```

### Custom Renderers

Implement the `Renderer` interface or use `CustomRenderer`:

```php
// Using CustomRenderer
$scrutiny->registerCustomRenderer('MyVerifier', function (Verifier $verifier) {
    return Element::create('div.my-captcha', [
        // Custom HTML
    ]);
});

// Or implement Renderer interface
class MyRenderer implements Renderer
{
    public function render(Verifier $verifier): Element
    {
        // Custom rendering logic
    }
}
```

### Compound Verifiers

Create compound verifiers that try multiple verifiers:

```php
$compound = new Verifier\Compound(
    ['Recaptcha', 'HCaptcha'],
    $scrutiny
);
```

## Interactions with Other Packages

- **Archetype**: Used for verifier class resolution via Slingshot.
- **Slingshot**: Used for dependency injection and verifier instantiation.
- **Hydro**: Used for HTTP requests to verification services.
- **Compass**: Used for IP address parsing and validation.
- **Tagged**: Used for HTML component rendering.
- **Horizon**: Used for HTML document component properties.
- **Monarch**: Used for service location.
- **Kingdom**: Used for service container integration.
- **Dovetail**: Optional configuration integration.
- **Nuance**: Used for debugging and sensitive property handling.

## Usage Examples

### Basic Verification

```php
use DecodeLabs\Scrutiny;

$scrutiny = new Scrutiny();

// Verify with values from form
$result = $scrutiny->verify(
    verifierName: 'Recaptcha',
    values: [
        'g-recaptcha-response' => $_POST['g-recaptcha-response']
    ],
    action: 'login',
    scoreThreshold: 0.5
);

if ($result->isValid()) {
    // Verification passed
} else {
    // Handle errors
    foreach ($result->errors as $error) {
        echo $error->value;
    }
}
```

### Using Payload

```php
use DecodeLabs\Scrutiny;
use DecodeLabs\Scrutiny\Payload;

$payload = $scrutiny->createPayload(
    verifierName: 'Recaptcha',
    values: ['g-recaptcha-response' => $token],
    action: 'submit',
    scoreThreshold: 0.7,
    timeout: 300
);

$result = $scrutiny->verifyPayload($payload);
```

### Hostname Validation

```php
$scrutiny = new Scrutiny();
$scrutiny->addHostNames('example.com', 'www.example.com');

// Hostname validation happens automatically in Result
$result = $scrutiny->verify('Recaptcha', $values);
```

### Tagged Component

```php
use DecodeLabs\Tagged\Component\Scrutiny as ScrutinyComponent;

// Render CAPTCHA widget
$component = new ScrutinyComponent('Recaptcha');
echo $component->renderInline();

// Or in Horizon document
$document->body->addChild(
    new ScrutinyComponent('Recaptcha', settings: ['siteKey' => '...'])
);
```

### Custom Renderer

```php
use DecodeLabs\Scrutiny\Renderer\Custom;
use DecodeLabs\Tagged\Element;

$scrutiny->registerCustomRenderer('Recaptcha', function ($verifier) {
    return Element::create('div.custom-captcha', [
        Element::create('div.g-recaptcha', [
            'data-sitekey' => $verifier->componentData['siteKey']
        ])
    ]);
});
```

### Compound Verifier

```php
use DecodeLabs\Scrutiny\Verifier\Compound;

$compound = new Compound(['Recaptcha', 'HCaptcha'], $scrutiny);

// Tries Recaptcha first, falls back to HCaptcha if Recaptcha data not present
$result = $compound->verify($payload);
```

### Configuration via Dovetail

```php
// In Dovetail config
[
    'Scrutiny' => [
        'Recaptcha' => [
            'enabled' => true,
            'siteKey' => '{{Env::asString("RECAPTCHA_SITE_KEY")}}',
            'secret' => '{{Env::asString("RECAPTCHA_SECRET")}}'
        ]
    ]
]

// Load verifier (uses config automatically)
$verifier = $scrutiny->loadVerifier('Recaptcha');
```

## Implementation Notes (for Contributors)

### Verifier Resolution

- Verifiers are resolved using Slingshot's `resolveNamedInstance()`.
- Verifier name maps to class name (e.g., 'Recaptcha' → `Verifier\Recaptcha`).
- Settings are passed to verifier constructor.
- Verifiers must be registered with Slingshot or Archetype.

### IP Address Extraction

- IP extraction checks multiple server variables in order.
- X-Forwarded-For may contain multiple IPs (comma-separated).
- First valid IP from the list is used.
- IP parsing uses Compass for validation.

### Score Normalization

- reCAPTCHA scores are inverted (1 - score) because reCAPTCHA uses 0 = bot, 1 = human.
- hCaptcha scores are used as-is (0 = bot, 1 = human).
- Scores are clamped to 0-1 range.

### Response Validation

- Validation happens in `Result` constructor.
- Hostname, action, score, and timeout are validated automatically.
- Validation errors are added to errors array.
- Errors are deduplicated.

### Component Rendering

- Renderers are looked up by verifier class short name.
- Default renderer is used if verifier-specific renderer not found.
- Generic renderer creates elements based on verifier class name.
- Component data is passed as HTML attributes (slugified).

### Asset Preparation

- Verifiers prepare assets via `prepareAssets()` method.
- Assets are added to Tagged component's script/link collections.
- Scripts can include nonce for CSP compliance.
- Assets are rendered inline or in document head/body as appropriate.

## Testing & Quality

**Current Status:**
- Code quality: 4/5
- README quality: 3/5
- Documentation: 0/5 (no formal docs yet)
- Tests: 0/5 (no test suite yet)

**Testing Considerations:**
- Verifier loading should be tested for:
  - Enabled/disabled verifiers
  - Missing verifiers
  - Settings application
  - Default verifier selection

- Verification should be tested for:
  - Successful verification
  - Failed verification
  - HTTP errors
  - Service error codes
  - Invalid payloads

- Payload validation should be tested for:
  - IP extraction from various server variables
  - Hostname validation
  - Action validation
  - Score threshold validation
  - Timeout validation

- Result validation should be tested for:
  - Automatic validation in constructor
  - Error deduplication
  - Valid/invalid status

- Renderers should be tested for:
  - Generic renderer output
  - Custom renderer output
  - Default renderer fallback
  - Component data formatting

## Roadmap & Future Ideas

- **Additional verifiers**: Support for more CAPTCHA services (Turnstile, etc.)
- **Rate limiting**: Built-in rate limiting for verification attempts
- **Analytics**: Verification analytics and reporting
- **Caching**: Response caching for repeated verifications
- **Testing mode**: Testing mode for development environments
- **Batch verification**: Support for batch verification of multiple tokens
- **Webhook support**: Support for webhook-based verification
- **Custom validation**: More flexible validation rules

## References

- Package repository: https://github.com/decodelabs/scrutiny
- Composer package: https://packagist.org/packages/decodelabs/scrutiny
- Related packages:
  - `decodelabs/archetype` — Class resolution
  - `decodelabs/slingshot` — Dependency injection
  - `decodelabs/hydro` — HTTP client
  - `decodelabs/compass` — IP address parsing
  - `decodelabs/tagged` — HTML rendering
  - `decodelabs/horizon` — HTML document components
  - `decodelabs/monarch` — Service location
  - `decodelabs/kingdom` — Service container
  - `decodelabs/dovetail` — Configuration (optional)

