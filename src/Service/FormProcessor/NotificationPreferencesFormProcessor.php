<?php

namespace Wexample\SymfonyNotificationDs\Service\FormProcessor;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyNotification\Service\NotificationCategoryRegistry;
use Wexample\SymfonyNotification\Service\NotificationPreferenceService;
use Wexample\SymfonyNotificationDs\Form\NotificationPreferencesForm;

/**
 * The channels the signed-in account keeps, per category it may receive.
 */
class NotificationPreferencesFormProcessor extends AbstractFormProcessor
{
    /** Where each channel is named, read here: the labels are built before the form renders. */
    private const string CHANNEL_DOMAIN = 'WexampleSymfonyNotificationDsBundle.forms.notification_preferences_form::channel.';

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly NotificationCategoryRegistry $categories,
        private readonly NotificationPreferenceService $preferences,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        $recipient = $this->getRecipient();
        $values = [];

        foreach ($this->preferences->getChoices($recipient, $this->categories->forRecipient($recipient)) as $category => $channels) {
            foreach ($channels as $channel => $enabled) {
                $values[NotificationPreferencesForm::getFieldName($category, $channel)] = $enabled;
            }
        }

        return parent::createForm($values, $options + [NotificationPreferencesForm::OPTION_FIELDS => $this->getFields()]);
    }

    public function onValid(FormInterface $form)
    {
        $recipient = $this->getRecipient();

        foreach ($this->getFields() as $name => $field) {
            $this->preferences->set($recipient, $field['category'], $field['channel'], $form->get($name)->getData() === true);
        }

        $this->setNotification('@form::success.message');
    }

    /**
     * @return array<string, array{label: string, category: string, channel: string, group: ?string}>
     */
    private function getFields(): array
    {
        $fields = [];

        foreach ($this->categories->forRecipient($this->getRecipient()) as $category) {
            foreach ($category->channels as $channel) {
                $fields[NotificationPreferencesForm::getFieldName($category->name, $channel)] = [
                    'label' => $this->translator->trans($category->getLabel()) . ' — ' . $this->translator->trans(self::CHANNEL_DOMAIN . $channel),
                    'category' => $category->name,
                    'channel' => $channel,
                    'group' => $category->group === null ? null : $this->translator->trans($category->group),
                ];
            }
        }

        return $fields;
    }

    private function getRecipient(): object
    {
        return $this->security->getUser() ?? throw new AccessDeniedHttpException();
    }
}
