<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Pumukit\NewAdminBundle\Form\Type\Other\Html5dateType;
use Symfony\Component\Translation\TranslatorInterface;

class MultimediaObjectMetaType extends AbstractType
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
            ->add('i18n_title', 'texti18n',
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Title', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Title', array(), null, $this->locale), ))
            ->add('i18n_subtitle', 'texti18n',
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Subtitle', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Subtitle', array(), null, $this->locale), ))
            ->add('i18n_description', 'textareai18n',
                  array('required' => false,
                        'attr' => array('style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Description', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Description', array(), null, $this->locale), ))
            ->add('comments', 'textarea',
                array('required' => false,
                    'attr' => array('style' => 'resize:vertical;'),
                    'label' => $this->translator->trans('Comments', array(), null, $this->locale), ))
            ->add('i18n_keyword', 'texti18n',
                  array('required' => false,
                        'attr' => array('class' => 'mmobj materialtags', 'aria-label' => $this->translator->trans('Keywords', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Keywords', array(), null, $this->locale), ))
            ->add('copyright', 'text',
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Copyright', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Copyright', array(), null, $this->locale), ))
            ->add('license', 'license',
                  array('required' => false,
                        'attr' => array('aria-label' => $this->translator->trans('License', array(), null, $this->locale)),
                        'label' => $this->translator->trans('License', array(), null, $this->locale), ))
            ->add('public_date', new Html5dateType(),
                  array('data_class' => 'DateTime',
                        'attr' => array('aria-label' => $this->translator->trans('Publication Date', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Publication Date', array(), null, $this->locale), ))
            ->add('record_date', new Html5dateType(),
                  array('data_class' => 'DateTime',
                        'attr' => array('aria-label' => $this->translator->trans('Recording Date', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Recording Date', array(), null, $this->locale), ))
            ->add('i18n_line2', 'textareai18n',
                  array('required' => false,
                        'attr' => array('groupclass' => 'hidden-naked', 'style' => 'resize:vertical;', 'aria-label' => $this->translator->trans('Headline', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Headline', array(), null, $this->locale), ))
            ->add('subseries', 'checkbox',
                  array('mapped' => false,
                        'required' => false,
                        'attr' => array('groupclass' => 'hidden-naked',
                                        'aria-label' => $this->translator->trans('Subseries', array(), null, $this->locale), ),
                        'label' => $this->translator->trans('Subseries', array(), null, $this->locale), ))
            ->add('subseriestitle', 'texti18n',
                  array('mapped' => false,
                        'required' => false,
                        'attr' => array('groupclass' => 'hidden-naked', 'aria-label' => $this->translator->trans('Subseries', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Subseries', array(), null, $this->locale), ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $multimediaObject = $event->getData();
            $event->getForm()->get('subseries')->setData($multimediaObject->getProperty('subseries'));
            $event->getForm()->get('subseriestitle')->setData($multimediaObject->getProperty('subseriestitle'));
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $subseries = $event->getForm()->get('subseries')->getData();
            $subseriestitle = $event->getForm()->get('subseriestitle')->getData();
            $multimediaObject = $event->getData();
            $multimediaObject->setProperty('subseries', $subseries);
            $multimediaObject->setProperty('subseriestitle', $subseriestitle);
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
        ));
    }

    public function getName()
    {
        return 'pumukitnewadmin_mms_meta';
    }
}
