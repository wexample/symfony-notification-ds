<?php

namespace Wexample\SymfonyNotificationDs\Twig;

use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyNotification\Service\NotificationInboxService;

class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly NotificationInboxService $inbox,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            // The unread notifications of the signed-in account, for a badge
            // in the user menu: 0 for nobody signed in.
            new TwigFunction('notification_unread_count', $this->inbox->countUnread(...)),
        ];
    }
}
