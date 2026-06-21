<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp;

use Docsdog\DocsdogPhp\Exception\ValidationException;

/**
 * Immutable value object representing a semantic relationship predicate.
 *
 * Standard predicates are available as named constructors.
 * Arbitrary predicates can be created via Predicate::of().
 *
 * Predicate format: lowercase letters, digits, and hyphens, starting with a letter.
 */
final class Predicate implements \JsonSerializable, \Stringable
{
    // ── Traceability ──

    public const IMPLEMENTS = 'implements';
    public const TRACES_TO = 'traces-to';
    public const REQUIRES = 'requires';
    public const VALIDATES = 'validates';
    public const TESTS = 'tests';

    // ── Architecture ──

    public const DEPENDS_ON = 'depends-on';
    public const OWNED_BY = 'owned-by';
    public const DECISION = 'decision';
    public const REPLACES = 'replaces';
    public const DEPRECATED_BY = 'deprecated-by';

    // ── Messaging ──

    public const EMITS = 'emits';
    public const CONSUMES = 'consumes';

    // ── Persistence ──

    public const PERSISTS = 'persists';
    public const MAPS_TO = 'maps-to';

    // ── API ──

    public const EXPOSES = 'exposes';

    // ── Security ──

    public const AUTHENTICATED_BY = 'authenticated-by';
    public const AUTHORIZED_BY = 'authorized-by';
    public const SECURED_BY = 'secured-by';

    // ── Configuration ──

    public const CONFIGURED_BY = 'configured-by';
    public const FEATURE_FLAG = 'feature-flag';

    // ── Standard predicates list (for introspection) ──

    public const ALL = [
        self::IMPLEMENTS,
        self::TRACES_TO,
        self::REQUIRES,
        self::VALIDATES,
        self::TESTS,
        self::DEPENDS_ON,
        self::OWNED_BY,
        self::DECISION,
        self::REPLACES,
        self::DEPRECATED_BY,
        self::EMITS,
        self::CONSUMES,
        self::PERSISTS,
        self::MAPS_TO,
        self::EXPOSES,
        self::AUTHENTICATED_BY,
        self::AUTHORIZED_BY,
        self::SECURED_BY,
        self::CONFIGURED_BY,
        self::FEATURE_FLAG,
    ];

    private function __construct(
        public readonly string $value,
    ) {}

    // ── Named constructors for standard predicates ──

    public static function implements(): self { return new self(self::IMPLEMENTS); }
    public static function tracesTo(): self { return new self(self::TRACES_TO); }
    public static function requires(): self { return new self(self::REQUIRES); }
    public static function validates(): self { return new self(self::VALIDATES); }
    public static function tests(): self { return new self(self::TESTS); }
    public static function dependsOn(): self { return new self(self::DEPENDS_ON); }
    public static function ownedBy(): self { return new self(self::OWNED_BY); }
    public static function decision(): self { return new self(self::DECISION); }
    public static function replaces(): self { return new self(self::REPLACES); }
    public static function deprecatedBy(): self { return new self(self::DEPRECATED_BY); }
    public static function emits(): self { return new self(self::EMITS); }
    public static function consumes(): self { return new self(self::CONSUMES); }
    public static function persists(): self { return new self(self::PERSISTS); }
    public static function mapsTo(): self { return new self(self::MAPS_TO); }
    public static function exposes(): self { return new self(self::EXPOSES); }
    public static function authenticatedBy(): self { return new self(self::AUTHENTICATED_BY); }
    public static function authorizedBy(): self { return new self(self::AUTHORIZED_BY); }
    public static function securedBy(): self { return new self(self::SECURED_BY); }
    public static function configuredBy(): self { return new self(self::CONFIGURED_BY); }
    public static function featureFlag(): self { return new self(self::FEATURE_FLAG); }

    // ── Factory for arbitrary predicates ──

    /**
     * Create a predicate from any valid predicate string.
     *
     * @throws ValidationException if the string is not a valid predicate.
     */
    public static function of(string $value): self
    {
        if (! self::isValid($value)) {
            throw ValidationException::invalidPredicate($value);
        }

        return new self($value);
    }

    /**
     * Check whether a string is a syntactically valid predicate.
     */
    public static function isValid(string $value): bool
    {
        return \preg_match('#^[a-z][a-z0-9-]*$#', $value) === 1;
    }

    /**
     * Whether this is one of the standard predicates defined by the spec.
     */
    public function isStandard(): bool
    {
        return \in_array($this->value, self::ALL, true);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
