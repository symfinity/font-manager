<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Import;

interface FontPairingImportPort
{
    public function import(string $source): PairingImportResult;
}
