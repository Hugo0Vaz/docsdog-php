<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp\Model;

/**
 * Immutable value object representing a complete DocsDog scan result.
 *
 * A Scan is the top-level container for a set of relationships,
 * tagged with a version string. It maps directly to the scan.schema.json format.
 */
final class Scan implements \JsonSerializable
{
    /**
     * @param Relationship[] $relationships
     */
    private function __construct(
        private readonly string $version,
        private readonly array $relationships,
    ) {}

    /**
     * Create a Scan from a version and a list of relationships.
     *
     * @param Relationship[] $relationships
     */
    public static function of(string $version, array $relationships): self
    {
        return new self($version, \array_values($relationships));
    }

    /**
     * Create a Scan from an associative array (e.g., decoded from JSON).
     *
     * Expected shape:
     *   {
     *     "version": "1.0",
     *     "relationships": [ ... ]
     *   }
     *
     * @param array{version: string, relationships: array<array<string, mixed>>} $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['version']) || ! \is_string($data['version'])) {
            throw new \InvalidArgumentException('Scan data must contain a string "version" field.');
        }

        $rawRelationships = $data['relationships'] ?? [];

        if (! \is_array($rawRelationships)) {
            throw new \InvalidArgumentException('Scan data "relationships" must be an array.');
        }

        $relationships = \array_map(
            static fn (array $rel): Relationship => Relationship::fromArray($rel),
            $rawRelationships,
        );

        return new self($data['version'], $relationships);
    }

    /**
     * Deserialize a Scan from a JSON string.
     *
     * @throws \JsonException if the JSON is malformed.
     */
    public static function fromJson(string $json): self
    {
        $data = \json_decode($json, true, flags: \JSON_THROW_ON_ERROR);

        if (! \is_array($data)) {
            throw new \InvalidArgumentException('JSON must decode to an object/array.');
        }

        return self::fromArray($data);
    }

    public function version(): string
    {
        return $this->version;
    }

    /**
     * @return Relationship[]
     */
    public function relationships(): array
    {
        return $this->relationships;
    }

    /**
     * Number of relationships in this scan.
     */
    public function count(): int
    {
        return \count($this->relationships);
    }

    /**
     * Convert to an associative array suitable for JSON serialization.
     *
     * @return array{version: string, relationships: array<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'relationships' => \array_map(
                static fn (Relationship $rel): array => $rel->toArray(),
                $this->relationships,
            ),
        ];
    }

    /**
     * Serialize to JSON string.
     *
     * @throws \JsonException
     */
    public function toJson(int $flags = 0): string
    {
        return \json_encode($this, $flags | \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{version: string, relationships: array<array<string, mixed>>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
