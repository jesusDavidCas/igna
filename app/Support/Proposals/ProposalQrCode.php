<?php

namespace App\Support\Proposals;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

class ProposalQrCode
{
    public function dataUri(string $url): string
    {
        return (new Builder(
            writer: new SvgWriter(),
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 180,
            margin: 8,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(82, 102, 74),
            backgroundColor: new Color(255, 255, 255),
        ))->build()->getDataUri();
    }
}
