<?php

declare(strict_types=1);

namespace App\Application;

/**
 * Handles creation of invoices from purchase orders.
 *
 * @docdog implements docdog:usecase:UC-001
 * @docdog decision docdog:adr:ADR-004
 */
final class CreateInvoiceService
{
    /**
     * Validates business rules before persisting.
     *
     * @docdog requires docdog:requirement:REQ-014
     */
    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly EventBus $eventBus,
    ) {}

    /**
     * Execute the use case.
     *
     * @docdog validates docdog:rule:BR-008
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
     * @docdog emits docdog:event:InvoiceCreated
     */
    private function dispatchEvents(Invoice $invoice): void
    {
        foreach ($invoice->releaseEvents() as $event) {
            $this->eventBus->publish($event);
        }
    }
}
