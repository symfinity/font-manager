<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Enum;

enum ProviderFeature: string
{
    case SEARCH = 'search';
    case METADATA = 'metadata';
    case CDN = 'cdn';
    case VARIABLE_FONTS = 'variable_fonts';
}
