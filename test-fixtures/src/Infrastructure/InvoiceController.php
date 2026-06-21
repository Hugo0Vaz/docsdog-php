<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * HTTP controller for invoice endpoints.
 *
 * @docsdog exposes docsdog:api:POST:/invoices
 * @docsdog secured-by docsdog:rule:SEC-001
 */
final class InvoiceController
{
    /**
     * @docsdog consumes docsdog:command:CreateInvoice
     */
    public function __invoke(): void {}
}
