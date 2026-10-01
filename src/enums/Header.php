<?php

namespace GlueAgency\LiteSpeed\enums;

enum Header: string
{
    case CACHE_CONTROL = 'X-LiteSpeed-Cache-Control';
    case TAG = 'X-LiteSpeed-Tag';
    case PURGE = 'X-LiteSpeed-Purge';
    case VARY = 'X-LiteSpeed-Vary';
}
