<?php

namespace App\Services\Integrations;

interface BarcodeScanner
{
    public function parse(string $input): array;
}
