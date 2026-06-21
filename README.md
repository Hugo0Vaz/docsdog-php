# DocsDog PHP

PHP implementation of the [DocDog specification](https://github.com/Hugo0Vaz/docsdog).

## Installation

```bash
composer require docdog/docsdog-php
```

## Annotation Format

Relationships are declared inside PHP docblocks using the `@docdog` annotation:

```
@docdog <predicate> <namespace>:<kind>:<identifier>
```

Example:

```php
/**
 * Handles creation of invoices from purchase orders.
 *
 * @docdog implements docdog:usecase:UC-001
 * @docdog decision docdog:adr:ADR-004
 */
final class CreateInvoiceService
{
    /**
     * @docdog requires docdog:requirement:REQ-014
     */
    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * @docdog validates docdog:rule:BR-008
     */
    public function execute(CreateInvoiceCommand $command): Invoice
    {
        $invoice = Invoice::create($command);
        $this->repository->save($invoice);
        return $invoice;
    }

    /**
     * @docdog emits docdog:event:InvoiceCreated
     */
    private function dispatchEvents(Invoice $invoice): void
    {
        foreach ($invoice->releaseEvents() as $event) {
            $this->eventBus->publish($event);
        }
    }
}
```

Annotations are associated with the next code element (class, method, function,
interface, trait, or enum) following the docblock. Multiple annotations per
docblock are supported.

## CLI

```bash
php vendor/bin/docdog scan [options]
```

### Options

| Option            | Default  | Description                      |
|-------------------|----------|----------------------------------|
| `--src=<dir>`     | `src`    | Source directory to scan         |
| `--output=<file>` | stdout   | Output file path                 |
| `--version=<ver>` | `1.0`    | Scan version tag                 |
| `--pretty`        | off      | Pretty-print JSON output         |
| `--help`, `-h`    | —        | Show help message                |

### Examples

```bash
# Scan src/ directory, write to file with pretty output
php vendor/bin/docdog scan --src=src --output=docdog-scan.json --pretty

# Scan app/ directory, print to stdout
php vendor/bin/docdog scan --src=app

# Pipe into another tool
php vendor/bin/docdog scan --src=src | your-tool
```

## Output Format

The scanner produces a JSON document conforming to the [scan schema](https://github.com/Hugo0Vaz/docsdog/blob/main/scan.schema.json):

```json
{
  "version": "1.0",
  "relationships": [
    {
      "source": "php://src/Application/CreateInvoiceService.php#L13",
      "predicate": "implements",
      "target": "docdog:usecase:UC-001"
    },
    {
      "source": "php://src/Application/CreateInvoiceService.php#L13",
      "predicate": "decision",
      "target": "docdog:adr:ADR-004"
    }
  ]
}
```

## Programmatic API

### Scanning files

```php
use Docsdog\DocsdogPhp\Scanner\PhpFileScanner;

$scanner = new PhpFileScanner();
$relationships = $scanner->scan('src/Application/CreateInvoiceService.php');

foreach ($relationships as $rel) {
    echo $rel->source()->toString();   // php://src/...php#L13
    echo $rel->predicate()->value;     // implements
    echo $rel->target()->toString();   // docdog:usecase:UC-001
}
```

### Building relationships manually

```php
use Docsdog\DocsdogPhp\Identifier\DocDogNamespace;
use Docsdog\DocsdogPhp\Identifier\SourceIdentifier;
use Docsdog\DocsdogPhp\Identifier\TargetIdentifier;
use Docsdog\DocsdogPhp\Model\Relationship;
use Docsdog\DocsdogPhp\Model\Scan;
use Docsdog\DocsdogPhp\Predicate;

$rel = Relationship::create(
    SourceIdentifier::parse('php://src/Service.php#L42'),
    Predicate::implements(),
    DocDogNamespace::usecase('UC-001'),
    ['since' => '2.1'], // optional metadata
);

$scan = Scan::of('1.0', [$rel]);
echo $scan->toJson(JSON_PRETTY_PRINT);
```

### Working with identifiers

```php
use Docsdog\DocsdogPhp\Identifier\DocDogNamespace;
use Docsdog\DocsdogPhp\Identifier\TargetIdentifier;

// Built-in docdog namespace (typed helpers)
$t1 = DocDogNamespace::requirement('REQ-014');    // docdog:requirement:REQ-014
$t2 = DocDogNamespace::api('post', '/invoices');   // docdog:api:POST:/invoices
$t3 = DocDogNamespace::table('invoices');          // docdog:table:invoices

// External namespaces (generic parser)
$t4 = TargetIdentifier::parse('jira:issue:ERP-123');
$t5 = TargetIdentifier::parse('github:repo:company/project#15');
```

## Running Tests

```bash
php test-suite.php
```
