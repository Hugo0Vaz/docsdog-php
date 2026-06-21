<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp\Identifier;

use Docsdog\DocsdogPhp\Exception\ValidationException;

/**
 * Immutable value object representing a target artifact identifier.
 *
 * Format: {namespace}:{kind}:{identifier}
 *
 * Examples:
 *   - docdog:usecase:UC-001
 *   - docdog:requirement:REQ-014
 *   - docdog:event:InvoiceCreated
 *   - jira:ERP-123
 *   - github:company/project#15
 */
final class TargetIdentifier implements \JsonSerializable, \Stringable
{
    private const PATTERN = '#^
        (?P<namespace>[a-z][a-z0-9-]*)
        :
        (?P<kind>[a-z][a-z0-9-]*)
        :
        (?P<identifier>.+)
    $#x';

    private function __construct(
        private readonly string $namespace,
        private readonly string $kind,
        private readonly string $identifier,
    ) {}

    /**
     * Parse a target identifier string.
     *
     * @throws ValidationException if the URI does not match the expected format.
     */
    public static function parse(string $uri): self
    {
        if (! \preg_match(self::PATTERN, $uri, $matches)) {
            throw ValidationException::invalidTargetIdentifier(
                $uri,
                'Expected format: {namespace}:{kind}:{identifier}'
            );
        }

        return new self(
            namespace: $matches['namespace'],
            kind: $matches['kind'],
            identifier: $matches['identifier'],
        );
    }

    /**
     * Create a target identifier from its components.
     */
    public static function fromParts(string $namespace, string $kind, string $identifier): self
    {
        return new self($namespace, $kind, $identifier);
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    /**
     * Whether this target belongs to the docdog built-in namespace.
     */
    public function isDocDog(): bool
    {
        return $this->namespace === 'docdog';
    }

    /**
     * Reconstruct the full target identifier URI.
     */
    public function toString(): string
    {
        return \sprintf('%s:%s:%s', $this->namespace, $this->kind, $this->identifier);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
