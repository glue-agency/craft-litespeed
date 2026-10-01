<?php

namespace GlueAgency\LiteSpeed\enums;

/**
 * LiteSpeed varies on any cookie named `_lscache_vary*` without being told to.
 */
enum VaryCookie: string
{
    case LOGGED_IN = '_lscache_vary';
}
