<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use PhpParser\Node\Expr\AssignOp\Mul;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class MultimediaObjectPubType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add(
                'status',
                ChoiceType::class,
                [
                    'choices' => [
                        'Published' => MultimediaObject::STATUS_PUBLISHED,
                        'Blocked' => MultimediaObject::STATUS_BLOQ,
                        'Hidden' => MultimediaObject::STATUS_HIDE,
                    ],
                    'disabled' => $options['not_granted_change_status'],
                    'attr' => ['aria-label' => $this->translator->trans('Status', [], null, $this->locale)],
                    'label' => $this->translator->trans('Status', [], null, $this->locale),
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => [
                        'Unknown' => MultimediaObject::TYPE_UNKNOWN,
                        'Video' => MultimediaObject::TYPE_VIDEO,
                        'Audio' => MultimediaObject::TYPE_AUDIO,
                        'External' => MultimediaObject::TYPE_EXTERNAL,
                        'Image' => MultimediaObject::TYPE_IMG,
                    ],
                    'attr' => ['aria-label' => $this->translator->trans('Type', [], null, $this->locale)],
                    'label' => $this->translator->trans('Type', [], null, $this->locale),
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Pumukit\SchemaBundle\Document\MultimediaObject',
                'not_granted_change_status' => true,
            ]
        );

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_mms_pub';
    }
}
