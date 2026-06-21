<?php

declare(strict_types=1);

namespace App\Application;

/**
 * Handles creation of invoices from purchase orders.
 *
 * @docsdog implements docsdog:usecase:UC-001
 * @docsdog decision docsdog:adr:ADR-004
 */
final class CreateInvoiceService
{
    /**
     * Validates business rules before persisting.
     *
     * @docsdog requires docsdog:requirement:REQ-014
     */
    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * Execute the use case.
     *
     * @docsdog validates docsdog:rule:BR-008
     */
    public function execute(CreateInvoiceCommand $command): Invoice
    {
        $invoice = Invoice::create($command);

        $this->repository->save($invoice);

        return $invoice;
    }

    /**
     * Dispatch the domain event.
     *
     * @docsdog emits docsdog:event:InvoiceCreated
     */
    private function dispatchEvents(Invoice $invoice): void
    {
        foreach ($invoice->releaseEvents() as $event) {
            $this->eventBus->publish($event);
        }
    }
}
