<?php
declare(strict_types=1);
namespace Sales\Quote\Application\ExportQuotePdf;
use Sales\Quote\Domain\{QuoteId, QuoteRepository};
use Sales\Quote\Infrastructure\Pdf\QuotePdfGenerator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
#[AsMessageHandler(bus: 'command.bus')]
final class ExportQuotePdfHandler
{
    public function __construct(
        private readonly QuoteRepository  $repository,
        private readonly QuotePdfGenerator $pdfGenerator,
    ) {}
    public function __invoke(ExportQuotePdfCommand $command): string
    {
        $quote = $this->repository->get(QuoteId::fromString($command->quoteId));
        $path  = $this->pdfGenerator->generate($quote);
        $quote->markPdfGenerated($path);
        $this->repository->save($quote);
        return $path;
    }
}
