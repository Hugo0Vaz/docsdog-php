<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp\Identifier;

/**
 * Factory for creating TargetIdentifier instances within the built-in "docdog" namespace.
 *
 * Covers all standard artifact kinds defined in the DocDog specification §7.
 */
final class DocDogNamespace
{
    public const NAMESPACE = 'docdog';

    // ── Requirements & Analysis ──

    public static function requirement(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'requirement', $id);
    }

    public static function usecase(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'usecase', $id);
    }

    public static function userstory(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'userstory', $id);
    }

    public static function rule(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'rule', $id);
    }

    public static function adr(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'adr', $id);
    }

    // ── Domain Events & CQRS ──

    public static function event(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'event', $id);
    }

    public static function command(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'command', $id);
    }

    public static function query(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'query', $id);
    }

    // ── Domain Model ──

    public static function aggregate(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'aggregate', $id);
    }

    public static function entity(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'entity', $id);
    }

    // ── API ──

    public static function api(string $method, string $path): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'api', \strtoupper($method) . ':' . $path);
    }

    // ── Database ──

    public static function database(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'database', $id);
    }

    public static function table(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'table', $id);
    }

    public static function view(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'view', $id);
    }

    public static function index(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'index', $id);
    }

    // ── Infrastructure ──

    public static function queue(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'queue', $id);
    }

    public static function topic(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'topic', $id);
    }

    public static function bucket(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'bucket', $id);
    }

    public static function lambda(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'lambda', $id);
    }

    // ── Standards ──

    public static function rfc(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'rfc', $id);
    }

    public static function iso(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'iso', $id);
    }

    public static function soc2(string $id): TargetIdentifier
    {
        return TargetIdentifier::fromParts(self::NAMESPACE, 'soc2', $id);
    }
}
