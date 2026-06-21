<?php

declare(strict_types=1);

namespace Docsdog\DocsdogPhp\Scanner;

use Docsdog\DocsdogPhp\Identifier\SourceIdentifier;
use Docsdog\DocsdogPhp\Identifier\TargetIdentifier;
use Docsdog\DocsdogPhp\Model\Relationship;
use Docsdog\DocsdogPhp\Predicate;

/**
 * Scans a single PHP file and extracts @docdog annotations from docblocks.
 *
 * Each annotation line follows the format:
 *
 *   @docdog <predicate> <target>
 *
 * The scanner associates each annotation with the code element
 * (class, method, function, interface, trait, enum) that the docblock
 * immediately precedes.
 */
final class PhpFileScanner
{
    /**
     * Scan a PHP file and return all discovered DocDog relationships.
     *
     * @return Relationship[]
     */
    public function scan(string $filePath): array
    {
        if (! \file_exists($filePath) || ! \is_readable($filePath)) {
            throw new \InvalidArgumentException(\sprintf('File not found or not readable: %s', $filePath));
        }

        $source = \file_get_contents($filePath);

        if ($source === false) {
            throw new \RuntimeException(\sprintf('Failed to read file: %s', $filePath));
        }

        /** @var list<array{0: int, 1: string, 2: int}|string> */
        $tokens = \token_get_all($source);
        $relationships = [];

        for ($i = 0, $count = \count($tokens); $i < $count; $i++) {
            if (! \is_array($tokens[$i])) {
                continue;
            }

            // We only care about doc-comments
            if ($tokens[$i][0] !== \T_DOC_COMMENT) {
                continue;
            }

            $docblock = $tokens[$i][1];

            // Skip docblocks without @docdog annotations
            if (! \str_contains($docblock, '@docdog')) {
                continue;
            }

            $annotations = $this->parseDocDogAnnotations($docblock);

            if ($annotations === []) {
                continue;
            }

            // Find the element this docblock belongs to
            $element = $this->findDocblockOwner($tokens, $i);

            if ($element === null) {
                continue;
            }

            $sourceId = SourceIdentifier::fromParts(
                'php',
                $filePath,
                $element['line'],
            );

            foreach ($annotations as $annotation) {
                try {
                    $relationships[] = Relationship::create(
                        $sourceId,
                        Predicate::of($annotation['predicate']),
                        TargetIdentifier::parse($annotation['target']),
                    );
                } catch (\Throwable) {
                    // Skip invalid annotations gracefully — a scanner
                    // shouldn't crash on malformed data.
                    continue;
                }
            }
        }

        return $relationships;
    }

    /**
     * Parse @docdog annotations from a docblock string.
     *
     * Each annotation line: @docdog <predicate> <target>
     *
     * @return list<array{predicate: string, target: string}>
     */
    private function parseDocDogAnnotations(string $docblock): array
    {
        $annotations = [];

        if (\preg_match_all(
            '/^\s*\*\s*@docdog\s+(?P<predicate>[a-z][a-z0-9-]*)\s+(?P<target>[a-z][a-z0-9-]*:[a-z][a-z0-9-]*:.+)\s*$/m',
            $docblock,
            $matches,
            \PREG_SET_ORDER,
        ) === false) {
            return [];
        }

        foreach ($matches as $match) {
            $annotations[] = [
                'predicate' => $match['predicate'],
                'target' => $match['target'],
            ];
        }

        return $annotations;
    }

    /**
     * Find the code element that a docblock belongs to.
     *
     * Walks forward from the docblock token, skipping whitespace and
     * attributes, until a declaration token is found.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @param int $docblockIndex The index of the T_DOC_COMMENT token.
     * @return array{type: string, line: int}|null
     */
    private function findDocblockOwner(array $tokens, int $docblockIndex): ?array
    {
        $count = \count($tokens);

        // Declaration tokens we recognize as DocDog source elements
        $declarationTokens = [
            \T_CLASS,
            \T_FUNCTION,
            \T_INTERFACE,
            \T_TRAIT,
            \T_ENUM,
        ];

        for ($i = $docblockIndex + 1; $i < $count; $i++) {
            $token = $tokens[$i];

            // Skip whitespace, comments, and attributes
            if (\is_array($token)) {
                $id = $token[0];

                if ($id === \T_WHITESPACE || $id === \T_COMMENT || $id === \T_DOC_COMMENT) {
                    continue;
                }

                if (\defined('T_ATTRIBUTE') && $id === \T_ATTRIBUTE) {
                    // Skip the entire attribute block
                    $i = $this->skipAttribute($tokens, $i);
                    continue;
                }

                if (\in_array($id, $declarationTokens, true)) {
                    $type = \token_name($id);

                    // For functions inside classes, we want the class name context
                    // but the line still comes from this token.
                    return [
                        'type' => \strtolower($type),
                        'line' => $token[2],
                    ];
                }

                // Abstract/final/public/private/static/readonly modifiers
                // before a declaration — keep walking.
                if (\in_array($id, [
                    \T_ABSTRACT,
                    \T_FINAL,
                    \T_PUBLIC,
                    \T_PROTECTED,
                    \T_PRIVATE,
                    \T_STATIC,
                    \T_READONLY,
                ], true)) {
                    continue;
                }

                // Hit something unexpected — stop.
                break;
            }

            // Bare string tokens — unlikely after a docblock, but stop.
            break;
        }

        return null;
    }

    /**
     * Skip over a PHP 8 attribute block.
     *
     * @param list<array{0: int, 1: string, 2: int}|string> $tokens
     * @return int The index of the token after the attribute block.
     */
    private function skipAttribute(array $tokens, int $start): int
    {
        $count = \count($tokens);
        $depth = 1;
        $i = $start + 1;

        for (; $i < $count; $i++) {
            if (\is_array($tokens[$i])) {
                if ($tokens[$i][0] === \T_ATTRIBUTE) {
                    $depth++;
                }
            } elseif ($tokens[$i] === ']') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return $i - 1;
    }
}
