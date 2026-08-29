<?php

declare(strict_types=1);

namespace Symfinity\FontManager\Enum;

enum BuildToolType: string
{
    case ASSET_MAPPER = 'assetmapper';
    case WEBPACK = 'webpack';
    case VITE = 'vite';
    case UNKNOWN = 'unknown';
}
