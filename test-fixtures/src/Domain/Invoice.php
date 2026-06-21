<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Core invoice entity.
 *
 * @docsdog persists docsdog:table:invoices
 * @docsdog maps-to docsdog:entity:Invoice
 */
final class Invoice
{
    /**
     * @docsdog tests docsdog:usecase:UC-001
     */
    public static function create(CreateInvoiceCommand $command): self
    {
        return new self();
    }

    /**
     * @return object[]
     */
    public function releaseEvents(): array
    {
        return [];
    }
}
