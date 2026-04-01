<?php
declare(strict_types=1);
namespace Sales\Quote\Infrastructure\Pdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Sales\Quote\Domain\Quote;
final class QuotePdfGenerator
{
    public function __construct(private readonly string $outputDir) {}
    public function generate(Quote $quote): string
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        $html = $this->buildHtml($quote);
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'quote-' . $quote->id()->value() . '.pdf';
        $path = $this->outputDir . '/' . $filename;
        file_put_contents($path, $dompdf->output());
        return $filename;
    }
    private function buildHtml(Quote $quote): string
    {
        $phases = '';
        foreach ($quote->phases() as $phase) {
            $phases .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>',
                htmlspecialchars($phase->name()),
                htmlspecialchars($phase->requiredRole()->value),
                $phase->durationDays(),
                number_format($phase->dailyRate()->amount / 100, 2),
                number_format($phase->subtotal->amount / 100, 2),
            );
        }
        return sprintf(
            '<!DOCTYPE html><html><body>
            <h1>Nabídka</h1>
            <p>Platnost do: %s</p>
            <p>%s</p>
            <table border="1"><tr><th>Fáze</th><th>Role</th><th>Dny</th><th>Sazba/den</th><th>Mezisoučet</th></tr>%s</table>
            <p><strong>Celkem: %s %s</strong></p>
            </body></html>',
            htmlspecialchars($quote->validUntil()->format('Y-m-d')),
            htmlspecialchars($quote->notes()),
            $phases,
            number_format($quote->totalPrice()->amount / 100, 2),
            htmlspecialchars($quote->totalPrice()->currency),
        );
    }
}
