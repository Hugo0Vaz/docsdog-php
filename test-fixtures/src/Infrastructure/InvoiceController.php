<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * HTTP controller for invoice endpoints.
 *
 * @docdog exposes docdog:api:POST:/invoices
 * @docdog secured-by docdog:rule:SEC-001
 */
final class InvoiceController
{
    /**
     * @docdog consumes docdog:command:CreateInvoice
     */
    public function __invoke(): void {}
}
