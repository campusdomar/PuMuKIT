<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class GroupType extends AbstractType
{
    private $translator;
    private $locale;

    public function __construct(TranslatorInterface $translator, $locale = 'en')
    {
        $this->translator = $translator;
        $this->locale = $locale;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('key', 'text',
                  array('label' => $this->translator->trans('Key', array(), null, $this->locale)))
            ->add('name', 'text',
                  array('label' => $this->translator->trans('Name', array(), null, $this->locale)))
            ->add('comments', 'text',
                  array('label' => $this->translator->trans('Comments', array(), null, $this->locale)))
            ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'data_class' => 'Pumukit\SchemaBundle\Document\Group',
    ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_group';
    }
}
