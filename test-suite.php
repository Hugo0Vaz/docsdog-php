<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Docsdog\DocsdogPhp\Exception\ValidationException;
use Docsdog\DocsdogPhp\Identifier\DocDogNamespace;
use Docsdog\DocsdogPhp\Identifier\SourceIdentifier;
use Docsdog\DocsdogPhp\Identifier\TargetIdentifier;
use Docsdog\DocsdogPhp\Model\Relationship;
use Docsdog\DocsdogPhp\Model\Scan;
use Docsdog\DocsdogPhp\Predicate;

$pass = 0;
$fail = 0;

function test(string $name, callable $fn): void
{
    global $pass, $fail;
    try {
        $fn();
        printf("  \033[32m✓\033[0m %s\n", $name);
        $pass++;
    } catch (\AssertionError $e) {
        printf("  \033[31m✗\033[0m %s\n    %s\n", $name, $e->getMessage());
        $fail++;
    } catch (\Throwable $e) {
        printf("  \033[31m✗\033[0m %s\n    %s: %s\n", $name, get_class($e), $e->getMessage());
        $fail++;
    }
}

/**
 * Assert that a callable throws.
 */
function assert_throws(callable $fn, string $exceptionClass = \Throwable::class): void
{
    try {
        $fn();
    } catch (\Throwable $e) {
        if ($e instanceof $exceptionClass) {
            return; // expected
        }
        throw new \AssertionError(
            sprintf('Expected %s, got %s: %s', $exceptionClass, get_class($e), $e->getMessage()),
        );
    }

    throw new \AssertionError(sprintf('Expected %s but no exception was thrown.', $exceptionClass));
}

// ═══════════════════════════════════════════════════════════
// SourceIdentifier
// ═══════════════════════════════════════════════════════════

echo "── SourceIdentifier ──\n";

test('parse basic URI', function () {
    $s = SourceIdentifier::parse('php://src/Foo.php#L42');
    assert($s->scheme() === 'php');
    assert($s->path() === 'src/Foo.php');
    assert($s->line() === 42);
    assert($s->column() === null);
});

test('parse URI with column', function () {
    $s = SourceIdentifier::parse('php://src/Foo.php#L42:C15');
    assert($s->column() === 15);
});

test('parse non-php scheme', function () {
    $s = SourceIdentifier::parse('java://src/main/UserService.java#L88');
    assert($s->scheme() === 'java');
});

test('parse typescript scheme', function () {
    $s = SourceIdentifier::parse('typescript://src/auth/login.ts#L17');
    assert($s->scheme() === 'typescript');
});

test('round-trip toString', function () {
    $original = 'php://src/App/Service.php#L100:C3';
    assert((string) SourceIdentifier::parse($original) === $original);
});

test('round-trip toString without column', function () {
    $original = 'rust://src/payment.rs#L51';
    assert((string) SourceIdentifier::parse($original) === $original);
});

test('fromParts factory', function () {
    $s = SourceIdentifier::fromParts('php', 'src/test.php', 10, 5);
    assert((string) $s === 'php://src/test.php#L10:C5');
});

test('rejects uppercase scheme', function () {
    assert_throws(fn () => SourceIdentifier::parse('PHP://src/file.php#L1'), ValidationException::class);
});

test('rejects missing #L', function () {
    assert_throws(fn () => SourceIdentifier::parse('php://src/file.php'), ValidationException::class);
});

test('rejects empty string', function () {
    assert_throws(fn () => SourceIdentifier::parse(''), ValidationException::class);
});

test('rejects missing path', function () {
    assert_throws(fn () => SourceIdentifier::parse('php://#L1'), ValidationException::class);
});

test('rejects scheme starting with number', function () {
    assert_throws(fn () => SourceIdentifier::parse('2php://src/file.php#L1'), ValidationException::class);
});

test('jsonSerialize returns string', function () {
    $s = SourceIdentifier::parse('php://src/Foo.php#L42');
    assert(json_encode($s, JSON_UNESCAPED_SLASHES) === '"php://src/Foo.php#L42"');
});

// ═══════════════════════════════════════════════════════════
// TargetIdentifier
// ═══════════════════════════════════════════════════════════

echo "\n── TargetIdentifier ──\n";

test('parse docdog target', function () {
    $t = TargetIdentifier::parse('docdog:usecase:UC-001');
    assert($t->namespace() === 'docdog');
    assert($t->kind() === 'usecase');
    assert($t->identifier() === 'UC-001');
    assert($t->isDocDog());
});

test('parse external namespace (jira)', function () {
    $t = TargetIdentifier::parse('jira:issue:ERP-123');
    assert($t->namespace() === 'jira');
    assert($t->kind() === 'issue');
    assert($t->identifier() === 'ERP-123');
    assert(! $t->isDocDog());
});

test('parse github-like target', function () {
    $t = TargetIdentifier::parse('github:repo:company/project#15');
    assert($t->namespace() === 'github');
    assert($t->kind() === 'repo');
    assert($t->identifier() === 'company/project#15');
});

test('round-trip toString', function () {
    $original = 'docdog:event:InvoiceCreated';
    assert((string) TargetIdentifier::parse($original) === $original);
});

test('fromParts factory', function () {
    $t = TargetIdentifier::fromParts('aws', 'lambda', 'generate-report');
    assert((string) $t === 'aws:lambda:generate-report');
});

test('rejects uppercase namespace', function () {
    assert_throws(fn () => TargetIdentifier::parse('DocDog:usecase:UC-001'), ValidationException::class);
});

test('rejects only 2 parts', function () {
    assert_throws(fn () => TargetIdentifier::parse('docdog:usecase'), ValidationException::class);
});

test('rejects empty string', function () {
    assert_throws(fn () => TargetIdentifier::parse(''), ValidationException::class);
});

test('rejects namespace starting with number', function () {
    assert_throws(fn () => TargetIdentifier::parse('123doc:kind:id'), ValidationException::class);
});

test('jsonSerialize returns string', function () {
    $t = TargetIdentifier::parse('docdog:usecase:UC-001');
    assert(json_encode($t) === '"docdog:usecase:UC-001"');
});

// ═══════════════════════════════════════════════════════════
// DocDogNamespace helpers
// ═══════════════════════════════════════════════════════════

echo "\n── DocDogNamespace ──\n";

test('usecase helper', function () {
    $t = DocDogNamespace::usecase('UC-001');
    assert((string) $t === 'docdog:usecase:UC-001');
});

test('event helper', function () {
    $t = DocDogNamespace::event('InvoiceCreated');
    assert((string) $t === 'docdog:event:InvoiceCreated');
});

test('table helper', function () {
    $t = DocDogNamespace::table('invoices');
    assert((string) $t === 'docdog:table:invoices');
});

test('api helper formats method:path', function () {
    $t = DocDogNamespace::api('post', '/invoices');
    assert((string) $t === 'docdog:api:POST:/invoices');
});

test('requirement helper', function () {
    $t = DocDogNamespace::requirement('REQ-014');
    assert((string) $t === 'docdog:requirement:REQ-014');
});

test('adr helper', function () {
    $t = DocDogNamespace::adr('ADR-004');
    assert((string) $t === 'docdog:adr:ADR-004');
});

test('queue helper', function () {
    $t = DocDogNamespace::queue('payments');
    assert((string) $t === 'docdog:queue:payments');
});

test('rfc helper', function () {
    $t = DocDogNamespace::rfc('RFC-9110');
    assert((string) $t === 'docdog:rfc:RFC-9110');
});

// ═══════════════════════════════════════════════════════════
// Predicate
// ═══════════════════════════════════════════════════════════

echo "\n── Predicate ──\n";

test('standard predicate implements', function () {
    $p = Predicate::implements();
    assert($p->value === 'implements');
    assert($p->isStandard());
});

test('standard predicate feature-flag', function () {
    $p = Predicate::featureFlag();
    assert($p->value === 'feature-flag');
    assert($p->isStandard());
});

test('custom predicate via of()', function () {
    $p = Predicate::of('custom-action');
    assert($p->value === 'custom-action');
    assert(! $p->isStandard());
});

test('all standard predicates are valid', function () {
    foreach (Predicate::ALL as $predicate) {
        assert(Predicate::isValid($predicate), "Predicate '{$predicate}' should be valid");
    }
});

test('rejects uppercase predicate', function () {
    assert_throws(fn () => Predicate::of('IMPLEMENTS'), ValidationException::class);
});

test('rejects empty predicate', function () {
    assert_throws(fn () => Predicate::of(''), ValidationException::class);
});

test('rejects predicate starting with number', function () {
    assert_throws(fn () => Predicate::of('2nd-implements'), ValidationException::class);
});

test('isValid returns false for bad predicates', function () {
    assert(! Predicate::isValid('Invalid'));
    assert(! Predicate::isValid(''));
    assert(! Predicate::isValid('2bad'));
});

test('json serializes as string', function () {
    assert(json_encode(Predicate::requires()) === '"requires"');
});

test('string cast', function () {
    assert((string) Predicate::decision() === 'decision');
});

// ═══════════════════════════════════════════════════════════
// Relationship
// ═══════════════════════════════════════════════════════════

echo "\n── Relationship ──\n";

test('create basic relationship', function () {
    $rel = Relationship::create(
        SourceIdentifier::parse('php://src/Foo.php#L12'),
        Predicate::implements(),
        TargetIdentifier::parse('docdog:usecase:UC-001'),
    );

    assert($rel->source()->line() === 12);
    assert($rel->predicate()->value === 'implements');
    assert($rel->target()->identifier() === 'UC-001');
    assert($rel->metadata() === null);
});

test('create with metadata', function () {
    $rel = Relationship::create(
        SourceIdentifier::parse('php://src/Foo.php#L25'),
        Predicate::requires(),
        TargetIdentifier::parse('docdog:requirement:REQ-014'),
        ['since' => '2.1', 'critical' => true, 'tags' => ['payments', 'security']],
    );

    assert($rel->getMetadata('since') === '2.1');
    assert($rel->getMetadata('critical') === true);
    assert($rel->getMetadata('tags') === ['payments', 'security']);
    assert($rel->getMetadata('unknown', 'default') === 'default');
});

test('toArray without metadata', function () {
    $rel = Relationship::create(
        SourceIdentifier::parse('php://src/Foo.php#L1'),
        Predicate::tests(),
        TargetIdentifier::parse('docdog:usecase:UC-002'),
    );

    $arr = $rel->toArray();
    assert(! isset($arr['metadata']));
    assert($arr['source'] === 'php://src/Foo.php#L1');
    assert($arr['predicate'] === 'tests');
    assert($arr['target'] === 'docdog:usecase:UC-002');
});

test('toArray with metadata', function () {
    $rel = Relationship::create(
        SourceIdentifier::parse('php://src/Foo.php#L1'),
        Predicate::dependsOn(),
        TargetIdentifier::parse('docdog:entity:Customer'),
        ['since' => '2.1'],
    );

    $arr = $rel->toArray();
    assert(isset($arr['metadata']));
    assert($arr['metadata']['since'] === '2.1');
});

test('fromArray round-trip', function () {
    $original = [
        'source' => 'php://src/Foo.php#L12',
        'predicate' => 'implements',
        'target' => 'docdog:usecase:UC-001',
        'metadata' => ['author' => 'Architecture Team'],
    ];

    $rel = Relationship::fromArray($original);
    assert($rel->toArray() === $original);
});

test('jsonSerialize structure', function () {
    $rel = Relationship::create(
        SourceIdentifier::parse('php://src/Application/CreateInvoiceService.php#L12'),
        Predicate::implements(),
        TargetIdentifier::parse('docdog:usecase:UC-001'),
    );

    $decoded = json_decode(json_encode($rel), true);
    assert($decoded['source'] === 'php://src/Application/CreateInvoiceService.php#L12');
    assert($decoded['predicate'] === 'implements');
    assert($decoded['target'] === 'docdog:usecase:UC-001');
});

test('fromArray rejects missing source', function () {
    assert_throws(
        fn () => Relationship::fromArray(['predicate' => 'implements', 'target' => 'docdog:usecase:UC-001']),
        \InvalidArgumentException::class,
    );
});

test('fromArray validates bad source', function () {
    assert_throws(
        fn () => Relationship::fromArray(['source' => 'invalid', 'predicate' => 'implements', 'target' => 'docdog:usecase:UC-001']),
        ValidationException::class,
    );
});

test('fromArray validates bad predicate', function () {
    assert_throws(
        fn () => Relationship::fromArray(['source' => 'php://src/file.php#L1', 'predicate' => 'INVALID', 'target' => 'docdog:usecase:UC-001']),
        ValidationException::class,
    );
});

test('fromArray validates bad target', function () {
    assert_throws(
        fn () => Relationship::fromArray(['source' => 'php://src/file.php#L1', 'predicate' => 'implements', 'target' => 'bad-target']),
        ValidationException::class,
    );
});

// ═══════════════════════════════════════════════════════════
// Scan
// ═══════════════════════════════════════════════════════════

echo "\n── Scan ──\n";

test('create empty scan', function () {
    $scan = Scan::of('1.0', []);
    assert($scan->version() === '1.0');
    assert($scan->count() === 0);
});

test('toArray empty scan', function () {
    $scan = Scan::of('1.0', []);
    assert($scan->toArray() === ['version' => '1.0', 'relationships' => []]);
});

test('fromArray round-trip', function () {
    $data = [
        'version' => '1.0',
        'relationships' => [
            ['source' => 'php://src/A.php#L1', 'predicate' => 'implements', 'target' => 'docdog:usecase:UC-001'],
            ['source' => 'php://src/B.php#L2', 'predicate' => 'emits', 'target' => 'docdog:event:X'],
        ],
    ];

    $scan = Scan::fromArray($data);
    assert($scan->version() === '1.0');
    assert($scan->count() === 2);
    assert($scan->toArray() === $data);
});

test('load scan-example.json and verify structure', function () {
    $json = file_get_contents(__DIR__ . '/test-fixtures/scan-example.json');
    $scan = Scan::fromJson($json);

    assert($scan->version() === '1.0');
    assert($scan->count() === 4);

    $rels = $scan->relationships();

    // Relationship 1: implements docref:usecase:UC-001
    assert($rels[0]->source()->path() === 'src/Application/CreateInvoiceService.php');
    assert($rels[0]->source()->line() === 12);
    assert($rels[0]->source()->column() === null);
    assert($rels[0]->predicate()->value === 'implements');
    assert($rels[0]->target()->namespace() === 'docref');
    assert($rels[0]->target()->kind() === 'usecase');
    assert($rels[0]->target()->identifier() === 'UC-001');

    // Relationship 2: requires docref:requirement:REQ-014
    assert($rels[1]->source()->line() === 25);
    assert($rels[1]->predicate()->value === 'requires');
    assert($rels[1]->target()->identifier() === 'REQ-014');

    // Relationship 3: validates docref:rule:BR-008
    assert($rels[2]->source()->line() === 51);
    assert($rels[2]->predicate()->value === 'validates');
    assert($rels[2]->target()->identifier() === 'BR-008');

    // Relationship 4: emits docref:event:InvoiceCreated (with column)
    assert($rels[3]->source()->line() === 63);
    assert($rels[3]->source()->column() === 17);
    assert($rels[3]->predicate()->value === 'emits');
    assert($rels[3]->target()->identifier() === 'InvoiceCreated');
});

test('json round-trip preserves exact content', function () {
    $json = file_get_contents(__DIR__ . '/test-fixtures/scan-example.json');
    $scan = Scan::fromJson($json);

    $originalData = json_decode($json, true);
    $reData = json_decode($scan->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), true);

    assert($originalData === $reData, 'Re-serialized scan-example.json does not match original');
});

test('metadata round-trip through scan', function () {
    $rel = Relationship::create(
        SourceIdentifier::parse('php://src/X.php#L1'),
        Predicate::featureFlag(),
        TargetIdentifier::parse('docdog:requirement:REQ-001'),
        ['since' => '3.0', 'tags' => ['beta']],
    );

    $scan = Scan::of('1.0', [$rel]);
    $restored = Scan::fromJson($scan->toJson());

    $r = $restored->relationships()[0];
    assert($r->getMetadata('since') === '3.0');
    assert($r->getMetadata('tags') === ['beta']);
});

test('rejects scan without version', function () {
    assert_throws(
        fn () => Scan::fromArray(['relationships' => []]),
        \InvalidArgumentException::class,
    );
});

test('rejects scan with non-array relationships', function () {
    assert_throws(
        fn () => Scan::fromArray(['version' => '1.0', 'relationships' => 'not-array']),
        \InvalidArgumentException::class,
    );
});

test('rejects malformed JSON', function () {
    assert_throws(
        fn () => Scan::fromJson('{bad json}'),
        \JsonException::class,
    );
});

// ═══════════════════════════════════════════════════════════
// Spec §15 Example (full usage)
// ═══════════════════════════════════════════════════════════

echo "\n── Spec §15 Example ──\n";

test('full spec example', function () {
    $file = 'src/Application/CreateInvoiceService.php';

    $rels = [
        Relationship::create(
            SourceIdentifier::parse("php://{$file}#L12"),
            Predicate::implements(),
            DocDogNamespace::usecase('UC-001'),
        ),
        Relationship::create(
            SourceIdentifier::parse("php://{$file}#L25"),
            Predicate::requires(),
            DocDogNamespace::requirement('REQ-014'),
        ),
        Relationship::create(
            SourceIdentifier::parse("php://{$file}#L51"),
            Predicate::decision(),
            DocDogNamespace::adr('ADR-004'),
        ),
        Relationship::create(
            SourceIdentifier::parse("php://{$file}#L63:C17"),
            Predicate::emits(),
            DocDogNamespace::event('InvoiceCreated'),
        ),
    ];

    $scan = Scan::of('1.0', $rels);
    assert($scan->count() === 4);

    $r = $scan->relationships();
    assert($r[0]->predicate()->value === 'implements');
    assert($r[0]->target()->identifier() === 'UC-001');
    assert($r[2]->predicate()->value === 'decision');
    assert($r[2]->target()->identifier() === 'ADR-004');
});

test('external namespaces example', function () {
    $rels = [
        Relationship::create(
            SourceIdentifier::parse('php://src/Service.php#L80'),
            Predicate::decision(),
            TargetIdentifier::parse('github:issue:my-org/payment-service#14'),
        ),
        Relationship::create(
            SourceIdentifier::parse('php://src/Service.php#L95'),
            Predicate::persists(),
            TargetIdentifier::parse('docdog:table:invoices'),
        ),
        Relationship::create(
            SourceIdentifier::parse('php://src/Handler.php#L10'),
            Predicate::of('configured-by'),
            TargetIdentifier::parse('terraform:module:network'),
        ),
        Relationship::create(
            SourceIdentifier::parse('php://src/Controller.php#L5'),
            Predicate::exposes(),
            TargetIdentifier::parse('openapi:spec:billing:v1'),
        ),
    ];

    $scan = Scan::of('1.0', $rels);
    assert($scan->count() === 4);

    $json = $scan->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $decoded = json_decode($json, true);

    assert($decoded['relationships'][0]['target'] === 'github:issue:my-org/payment-service#14');
    assert($decoded['relationships'][2]['target'] === 'terraform:module:network');
    assert($decoded['relationships'][3]['target'] === 'openapi:spec:billing:v1');
});

// ═══════════════════════════════════════════════════════════
// Scanner (PhpFileScanner)
// ═══════════════════════════════════════════════════════════

echo "\n── Scanner ──\n";

$fixturesDir = __DIR__ . '/test-fixtures/src';

$scanner = new \Docsdog\DocsdogPhp\Scanner\PhpFileScanner();

test('scans CreateInvoiceService.php (5 relationships)', function () use ($scanner, $fixturesDir) {
    $path = $fixturesDir . '/Application/CreateInvoiceService.php';
    $rels = $scanner->scan($path);

    assert(count($rels) === 5);

    // Class-level annotations
    assert($rels[0]->predicate()->value === 'implements');
    assert((string) $rels[0]->target() === 'docdog:usecase:UC-001');
    assert($rels[1]->predicate()->value === 'decision');
    assert((string) $rels[1]->target() === 'docdog:adr:ADR-004');

    // Constructor
    assert($rels[2]->predicate()->value === 'requires');
    assert((string) $rels[2]->target() === 'docdog:requirement:REQ-014');

    // execute method
    assert($rels[3]->predicate()->value === 'validates');
    assert((string) $rels[3]->target() === 'docdog:rule:BR-008');

    // dispatchEvents method
    assert($rels[4]->predicate()->value === 'emits');
    assert((string) $rels[4]->target() === 'docdog:event:InvoiceCreated');
});

test('scans Invoice.php (3 relationships)', function () use ($scanner, $fixturesDir) {
    $rels = $scanner->scan($fixturesDir . '/Domain/Invoice.php');
    assert(count($rels) === 3);

    assert($rels[0]->predicate()->value === 'persists');
    assert($rels[1]->predicate()->value === 'maps-to');
    assert($rels[2]->predicate()->value === 'tests');
});

test('scans InvoiceController.php (3 relationships)', function () use ($scanner, $fixturesDir) {
    $rels = $scanner->scan($fixturesDir . '/Infrastructure/InvoiceController.php');
    assert(count($rels) === 3);

    assert($rels[0]->predicate()->value === 'exposes');
    assert((string) $rels[0]->target() === 'docdog:api:POST:/invoices');
    assert($rels[1]->predicate()->value === 'secured-by');
    assert($rels[2]->predicate()->value === 'consumes');
});

test('empty file returns no relationships', function () use ($scanner, $fixturesDir) {
    $emptyFile = sys_get_temp_dir() . '/docdog-empty.php';
    file_put_contents($emptyFile, '<?php // no annotations');
    $rels = $scanner->scan($emptyFile);
    assert(count($rels) === 0);
    unlink($emptyFile);
});

test('rejects non-existent file', function () use ($scanner) {
    assert_throws(
        fn () => $scanner->scan('/nonexistent/file.php'),
        \InvalidArgumentException::class,
    );
});

test('source identifiers point to declaration lines', function () use ($scanner, $fixturesDir) {
    $rels = $scanner->scan($fixturesDir . '/Application/CreateInvoiceService.php');

    // Class declaration is at line 13
    assert($rels[0]->source()->line() === 13);
    // Constructor at line 20
    assert($rels[2]->source()->line() === 20);
    // execute() at line 30
    assert($rels[3]->source()->line() === 30);
});

test('scanner output produces valid Scan', function () use ($scanner, $fixturesDir) {
    $allRels = [];
    foreach (['Application/CreateInvoiceService.php', 'Domain/Invoice.php', 'Infrastructure/InvoiceController.php'] as $relPath) {
        array_push($allRels, ...$scanner->scan($fixturesDir . '/' . $relPath));
    }

    $scan = Scan::of('1.0', $allRels);
    $json = $scan->toJson();
    $restored = Scan::fromJson($json);

    assert($restored->count() === 11);
    assert($restored->toArray() === $scan->toArray());
});

test('skips malformed annotations gracefully', function () use ($scanner, $fixturesDir) {
    $badFile = sys_get_temp_dir() . '/docdog-bad.php';
    file_put_contents($badFile, <<<'PHP'
<?php

/**
 * @docdog INVALID_PREDICATE docdog:usecase:UC-001
 * @docdog implements bad-target
 */
class BadService {}
PHP
    );
    $rels = $scanner->scan($badFile);
    // Both annotations are invalid — should be skipped, not crash
    assert(count($rels) === 0);
    unlink($badFile);
});

// ═══════════════════════════════════════════════════════════
// Summary
// ═══════════════════════════════════════════════════════════

echo "\n" . str_repeat('─', 50) . "\n";
echo "Results: {$pass} passed, {$fail} failed\n";
echo str_repeat('─', 50) . "\n";

exit($fail > 0 ? 1 : 0);
