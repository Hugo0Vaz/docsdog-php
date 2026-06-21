<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp\Identifier;

use Docsdog\DocsdogPhp\Exception\ValidationException;

/**
 * Immutable value object representing a source code location identifier.
 *
 * Format: {scheme}://{path}#L{line}(:C{column})?
 *
 * Examples:
 *   - php://src/Application/CreateInvoiceService.php#L42
 *   - php://src/Application/CreateInvoiceService.php#L42:C15
 *   - java://src/main/UserService.java#L88
 *   - typescript://src/auth/login.ts#L17
 */
final class SourceIdentifier implements \JsonSerializable, \Stringable
{
    private const PATTERN = '#^
        (?P<scheme>[a-z][a-z0-9+.-]*)
        ://
        (?P<path>.+)
        \#L(?P<line>[0-9]+)
        (:C(?P<column>[0-9]+))?
    $#x';

    private function __construct(
        private readonly string $scheme,
        private readonly string $path,
        private readonly int $line,
        private readonly ?int $column,
    ) {}

    /**
     * Parse a source identifier string.
     *
     * @throws ValidationException if the URI does not match the expected format.
     */
    public static function parse(string $uri): self
    {
        if (! \preg_match(self::PATTERN, $uri, $matches)) {
            throw ValidationException::invalidSourceIdentifier(
                $uri,
                'Expected format: {scheme}://{path}#L{line}(:C{column})?'
            );
        }

        return new self(
            scheme: $matches['scheme'],
            path: $matches['path'],
            line: (int) $matches['line'],
            column: isset($matches['column']) && $matches['column'] !== '' ? (int) $matches['column'] : null,
        );
    }

    /**
     * Create a source identifier from its components.
     */
    public static function fromParts(string $scheme, string $path, int $line, ?int $column = null): self
    {
        return new self($scheme, $path, $line, $column);
    }

    public function scheme(): string
    {
        return $this->scheme;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function line(): int
    {
        return $this->line;
    }

    public function column(): ?int
    {
        return $this->column;
    }

    /**
     * Reconstruct the full source identifier URI.
     */
    public function toString(): string
    {
        $uri = \sprintf('%s://%s#L%d', $this->scheme, $this->path, $this->line);

        if ($this->column !== null) {
            $uri .= \sprintf(':C%d', $this->column);
        }

        return $uri;
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
