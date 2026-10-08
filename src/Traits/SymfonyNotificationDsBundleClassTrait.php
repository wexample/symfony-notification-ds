<?php

namespace Wexample\SymfonyNotificationDs\Traits;

use Wexample\SymfonyHelpers\Traits\BundleClassTrait;
use Wexample\SymfonyNotificationDs\WexampleSymfonyNotificationDsBundle;

trait SymfonyNotificationDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyNotificationDsBundle::class;
    }
}
