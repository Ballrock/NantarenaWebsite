<?php

namespace Nantarena\ForumBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ThreadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'attr' => array(
                    'class' => 'input-block-level',
                ),
            ))
            ->add('content', 'textarea', array(
                'attr' => array(
                    'class' => 'input-block-level',
                    'rows' => 20,
                ),
                'mapped' => false,
                'constraints' => new NotBlank(),
            ))
            ->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Nantarena\ForumBundle\Entity\Thread',
        ));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'nantarena_forum_threadtype';
    }
}