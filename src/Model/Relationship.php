<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp\Model;

use Docsdog\DocsdogPhp\Exception\ValidationException;
use Docsdog\DocsdogPhp\Identifier\SourceIdentifier;
use Docsdog\DocsdogPhp\Identifier\TargetIdentifier;
use Docsdog\DocsdogPhp\Predicate;

/**
 * Immutable value object representing a single DocDog relationship.
 *
 * A directed edge: Source → Predicate → Target, with optional metadata.
 *
 * @psalm-import-type Metadata from self
 * @phpstan-type Metadata array<string, mixed>
 */
final class Relationship implements \JsonSerializable
{
    /**
     * @param Metadata|null $metadata Arbitrary key-value metadata.
     */
    private function __construct(
        private readonly SourceIdentifier $source,
        private readonly Predicate $predicate,
        private readonly TargetIdentifier $target,
        private readonly ?array $metadata,
    ) {}

    /**
     * Create a new relationship.
     *
     * @param array<string, mixed>|null $metadata
     */
    public static function create(
        SourceIdentifier $source,
        Predicate $predicate,
        TargetIdentifier $target,
        ?array $metadata = null,
    ): self {
        return new self($source, $predicate, $target, $metadata);
    }

    /**
     * Create a Relationship from an associative array (e.g., decoded from JSON).
     *
     * Expected shape:
     *   {
     *     "source": "php://...",
     *     "predicate": "implements",
     *     "target": "docdog:...",
     *     "metadata": { ... }   // optional
     *   }
     *
     * @param array<string, mixed> $data
     * @throws ValidationException if required fields are missing or invalid.
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['source']) || ! \is_string($data['source'])) {
            throw new \InvalidArgumentException('Relationship data must contain a string "source" field.');
        }

        if (! isset($data['predicate']) || ! \is_string($data['predicate'])) {
            throw new \InvalidArgumentException('Relationship data must contain a string "predicate" field.');
        }

        if (! isset($data['target']) || ! \is_string($data['target'])) {
            throw new \InvalidArgumentException('Relationship data must contain a string "target" field.');
        }

        $metadata = null;
        if (\array_key_exists('metadata', $data)) {
            $metadata = \is_array($data['metadata']) ? $data['metadata'] : null;
        }

        return new self(
            source: SourceIdentifier::parse($data['source']),
            predicate: Predicate::of($data['predicate']),
            target: TargetIdentifier::parse($data['target']),
            metadata: $metadata,
        );
    }

    public function source(): SourceIdentifier
    {
        return $this->source;
    }

    public function predicate(): Predicate
    {
        return $this->predicate;
    }

    public function target(): TargetIdentifier
    {
        return $this->target;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function metadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Get a specific metadata value by key.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to an associative array suitable for JSON serialization.
     *
     * @return array{source: string, predicate: string, target: string, metadata?: array<string, mixed>}
     */
    public function toArray(): array
    {
        $data = [
            'source' => $this->source->toString(),
            'predicate' => $this->predicate->value,
            'target' => $this->target->toString(),
        ];

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * @return array{source: string, predicate: string, target: string, metadata?: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
