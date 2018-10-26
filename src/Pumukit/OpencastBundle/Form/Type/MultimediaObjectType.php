<?php

namespace Pumukit\OpencastBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Pumukit\NewAdminBundle\Form\Type\Other\TrackdurationType;

class MultimediaObjectType extends AbstractType
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
            ->add('opencastinvert', 'checkbox',
                  array(
                        'required' => false,
                        'mapped' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Invert', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Invert', array(), null, $this->locale), ))
            ->add('opencastlanguage', 'customlanguage',
                  array(
                        'required' => true,
                        'mapped' => false,
                        'attr' => array('aria-label' => $this->translator->trans('Language', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Language', array(), null, $this->locale), ))
            ->add('durationinminutesandseconds', new TrackdurationType(),
                  array(
                        'required' => true,
                        'disabled' => true,
                        'attr' => array('aria-label' => $this->translator->trans('Duration', array(), null, $this->locale)),
                        'label' => $this->translator->trans('Duration', array(), null, $this->locale), ))
          ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $multimediaObject = $event->getData();
            $event->getForm()->get('opencastinvert')->setData($multimediaObject->getProperty('opencastinvert'));
            $event->getForm()->get('opencastlanguage')->setData($multimediaObject->getProperty('opencastlanguage'));
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $opencastInvert = $event->getForm()->get('opencastinvert')->getData();
            $opencastLanguage = strtolower($event->getForm()->get('opencastlanguage')->getData());
            $multimediaObject = $event->getData();
            $multimediaObject->setProperty('opencastinvert', $opencastInvert);
            $multimediaObject->setProperty('opencastlanguage', $opencastLanguage);
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
        return 'pumukit_opencast_multimedia_object';
    }
}
