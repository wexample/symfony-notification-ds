<?php

namespace Wexample\SymfonyNotificationDs\Controller\Pages;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyNotification\Entity\Notification;
use Wexample\SymfonyNotification\Routing\NotificationRoute;
use Wexample\SymfonyNotification\Service\NotificationInboxService;
use Wexample\SymfonyNotification\Service\NotificationPreferenceService;
use Wexample\SymfonyNotification\Service\NotificationTextService;
use Wexample\SymfonyNotificationDs\Service\FormProcessor\NotificationPreferencesFormProcessor;
use Wexample\SymfonyNotificationDs\Traits\SymfonyNotificationDsBundleClassTrait;

/**
 * The inbox of the signed-in account, its preferences, and the page the
 * unsubscribe link of a mail opens — this one without signing in, its link
 * being signed.
 */
#[Route(path: '/notifications')]
final class NotificationsController extends AbstractPagesController
{
    use SymfonyNotificationDsBundleClassTrait;

    private const int PER_PAGE = 20;

    /**
     * The application's form token id, which it declares stateless already:
     * the read-all button asks for no second declaration.
     */
    public const string CSRF_TOKEN_ID = 'submit';

    #[Route(path: '', name: NotificationRoute::INBOX)]
    #[IsGranted('IS_AUTHENTICATED')]
    public function index(
        Request $request,
        NotificationInboxService $inbox,
        NotificationTextService $texts
    ): Response {
        return $this->renderInbox($request, $inbox, $texts, false);
    }

    #[Route(path: '/unread', name: NotificationRoute::INBOX_UNREAD)]
    #[IsGranted('IS_AUTHENTICATED')]
    public function unread(
        Request $request,
        NotificationInboxService $inbox,
        NotificationTextService $texts
    ): Response {
        return $this->renderInbox($request, $inbox, $texts, true);
    }

    private function renderInbox(
        Request $request,
        NotificationInboxService $inbox,
        NotificationTextService $texts,
        bool $unreadOnly
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $found = $inbox->paginate($page, self::PER_PAGE, $unreadOnly);

        return $this->renderPage('index', [
            'unread_only' => $unreadOnly,
            'unread_count' => $inbox->countUnread(),
            'page' => $page,
            'pages_count' => $found['pages_count'],
            'count' => $found['count'],
            'csrf_token_id' => self::CSRF_TOKEN_ID,
            'rows' => array_map(fn (Notification $notification) => [
                'id' => (string) $notification->getId(),
                'level' => $notification->getLevel(),
                'title' => $texts->getTitle($notification, $request->getLocale()),
                'body' => $texts->getBody($notification, $request->getLocale()),
                'date' => $notification->getDateCreated(),
                'read' => $notification->isRead(),
                'has_url' => $texts->getUrl($notification) !== null,
            ], $found['notifications']),
        ]);
    }

    /**
     * Marks it read, then goes to its subject — or back to the inbox, for
     * one about nothing to open.
     */
    #[Route(path: '/{id}/open', name: NotificationRoute::INBOX_OPEN, requirements: ['id' => '[0-9a-f-]{36}'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function open(string $id, NotificationInboxService $inbox, NotificationTextService $texts): RedirectResponse
    {
        $notification = $inbox->markRead($id) ?? throw $this->createNotFoundException();

        return $this->redirect($texts->getUrl($notification) ?? $this->generateUrl(NotificationRoute::INBOX));
    }

    #[Route(path: '/read-all', name: NotificationRoute::INBOX_READ_ALL, methods: [Request::METHOD_POST])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function readAll(Request $request, NotificationInboxService $inbox): RedirectResponse
    {
        // A button, not a form the browser fills: the token tells a click on
        // the page from a click somewhere else.
        if (! $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $inbox->markAllRead();

        return $this->redirectToRoute(NotificationRoute::INBOX);
    }

    #[Route(path: '/preferences', name: NotificationRoute::PREFERENCES)]
    #[IsGranted('IS_AUTHENTICATED')]
    public function preferences(NotificationPreferencesFormProcessor $formProcessor): Response
    {
        return $this->renderPage('preferences', [
            'notification_preferences_form' => $formProcessor->createForm()->createView(),
        ]);
    }

    /**
     * Open to anyone holding the signed link a mail carries: the signature
     * says which recipient, category and channel.
     */
    #[Route(path: '/unsubscribe', name: NotificationRoute::UNSUBSCRIBE)]
    public function unsubscribe(Request $request, NotificationPreferenceService $preferences): Response
    {
        $notification = $preferences->unsubscribe($request);

        return $this->renderPage('unsubscribe', [
            'done' => $notification !== null,
        ], $notification ? null : new Response(status: Response::HTTP_NOT_FOUND));
    }
}
