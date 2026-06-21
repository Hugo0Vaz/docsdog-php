<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp\Exception;

final class ValidationException extends \InvalidArgumentException
{
    public static function invalidSourceIdentifier(string $uri, ?string $reason = null): self
    {
        $message = \sprintf('Invalid source identifier: "%s".', $uri);

        if ($reason !== null) {
            $message .= ' ' . $reason;
        }

        return new self($message);
    }

    public static function invalidTargetIdentifier(string $uri, ?string $reason = null): self
    {
        $message = \sprintf('Invalid target identifier: "%s".', $uri);

        if ($reason !== null) {
            $message .= ' ' . $reason;
        }

        return new self($message);
    }

    public static function invalidPredicate(string $predicate): self
    {
        return new self(\sprintf(
            'Invalid predicate: "%s". Predicate must be lowercase letters, digits, and hyphens only, starting with a letter.',
            $predicate,
        ));
    }
}
