<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http\Request;
final class AdvanceInquiryStatusRequest
{
    public function __construct(public readonly ?string $target_status = null) {}
}
