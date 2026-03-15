<?php

namespace Poradnik\Platform\Modules\RoleManager;

use Poradnik\Platform\Core\Roles;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_action('init', [Roles::class, 'register'], 1);
    }
}
