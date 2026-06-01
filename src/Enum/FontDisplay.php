<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Enum;

enum FontDisplay: string
{
    case SWAP = 'swap';
    case BLOCK = 'block';
    case AUTO = 'auto';
    case OPTIONAL = 'optional';
    case FALLBACK = 'fallback';
}
