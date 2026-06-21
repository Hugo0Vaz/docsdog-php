<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Core invoice entity.
 *
 * @docdog persists docdog:table:invoices
 * @docdog maps-to docdog:entity:Invoice
 */
final class Invoice
{
    /**
     * @docdog tests docdog:usecase:UC-001
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
