<?php

namespace Nantarena\EventBundle\Form\Type;

use Nantarena\SiteBundle\Form\Transformer\ImageTransformer;
use Nantarena\SiteBundle\Form\Transformer\ResourceTransformer;
use Nantarena\SiteBundle\Form\Type\ImageType;
use Nantarena\SiteBundle\Form\Type\ResourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $imageTransformer = new ImageTransformer();
        $resourceTransformer = new ResourceTransformer();

        $builder
            ->add('name', 'text')
            ->add('startDate', 'datetime', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'
            ))
            ->add('endDate', 'datetime', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'
            ))
            ->add('startRegistrationDate', 'datetime', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'
            ))
            ->add('endRegistrationDate', 'datetime', array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy HH:mm'
            ))
            ->add('capacity', 'integer', array(
                'attr' => array('min' => 1)
            ))
            ->add('entryTypes', 'collection', array(
                'type' => new EntryTypeType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
            ->add('tournaments', 'collection', array(
                'type' => new TournamentType(),
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ))
            ->add(
                $builder->create('cover', new ImageType(), array(
                    'required' => false,
                    'empty_message' => 'event.form.event.cover_empty'
                ))->addViewTransformer($imageTransformer)
            )
            ->add(
                $builder->create('rules', new ResourceType(), array(
                    'required' => false,
                    'empty_message' => 'event.form.event.rules_empty'
                ))->addViewTransformer($resourceTransformer)
            )
            ->add(
                $builder->create('autorization', new ResourceType(), array(
                    'required' => false,
                    'empty_message' => 'event.form.event.autorization_empty'
                ))->addViewTransformer($resourceTransformer)
            )
            ->add('submit', 'submit')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Nantarena\EventBundle\Entity\Event',
        ));
    }

    public function getName()
    {
        return 'nantarena_eventbundle_eventtype';
    }
}
