<?php

namespace Wexample\SymfonyNotificationDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\SwitchInputType;

/**
 * One switch per category and personal channel the account receives: on
 * unless it turned it off.
 */
class NotificationPreferencesForm extends AbstractForm
{
    /** Each field: its label, already read, its category, its channel, and the heading it is filed under. */
    public const string OPTION_FIELDS = 'preference_fields';

    public static bool $ajax = true;

    public static function getFieldName(string $category, string $channel): string
    {
        return preg_replace('/[^a-z0-9_]/', '_', strtolower($category)) . '__' . $channel;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(self::OPTION_FIELDS, []);
        $resolver->setAllowedTypes(self::OPTION_FIELDS, 'array');
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        foreach ($options[self::OPTION_FIELDS] as $name => $field) {
            $builder->add($name, SwitchInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => $field['label'],
                self::FIELD_OPTION_NAME_REQUIRED => false,
                // The label is the application's translation id, already read.
                'translation_domain' => false,
            ]);
        }

        $this->builderAddSubmit($builder);
    }

    /**
     * The heading of each switch, for the template to file them under.
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['preference_groups'] = array_map(
            static fn (array $field) => $field['group'],
            $options[self::OPTION_FIELDS]
        );
    }
}
