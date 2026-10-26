# symfony-notification-ds

Version: 2.0.9

Design-system screens for symfony-notification: the inbox, the preferences, the page an unsubscribe link opens

## Table of Contents

- [Installation](#installation)

## Installation

```php
// config/bundles.php
Wexample\SymfonyNotification\WexampleSymfonyNotificationBundle::class => ['all' => true],
Wexample\SymfonyNotificationDs\WexampleSymfonyNotificationDsBundle::class => ['all' => true],
```

The screens of symfony-notification, drawn with the design system.

| Route | Path | What |
|---|---|---|
| `notification_inbox` | `/notifications` | the inbox of the signed-in account, newest first, a line each, lit while unread |
| `notification_inbox_unread` | `/notifications/unread` | the unread ones only |
| `notification_inbox_open` | `/notifications/{id}/open` | marks it read, then goes to its subject — or back to the inbox |
| `notification_inbox_read_all` | `POST /notifications/read-all` | marks everything read, with the application's `submit` form token |
| `notification_preferences` | `/notifications/preferences` | a switch per category and personal channel, filed under the category's `group` |
| `notification_unsubscribe` | `/notifications/unsubscribe` | where the signed link of a mail leads, signed in or not |

The names are symfony-notification's `Routing\NotificationRoute`. `notification_unread_count()` gives the unread count of the signed-in account, for a badge in the user menu:

```twig
{ icon: 'ph:bold/bell', label: 'WexampleSymfonyNotificationDsBundle.pages.notifications.index::page_title' | trans ~ (notification_unread_count() ? ' (' ~ notification_unread_count() ~ ')' : ''), href: path('notification_inbox') },
```

A category's channels are named on the preferences page from `forms/notification_preferences_form.trans.yml` (`channel.mail`): an application adding a personal channel names it there, in its override of the file.
