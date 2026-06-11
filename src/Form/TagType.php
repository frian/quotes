<?php

namespace App\Form;

use App\Entity\Color;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du tag',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('color', EntityType::class, [
                'label' => 'Couleur',
                'class' => Color::class,
                'choice_label' => static fn (Color $color): string => sprintf('%s (%s)', $color->getName(), $color->getHexCode()),
                'placeholder' => 'Choisir une couleur',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['submit_label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'submit_label' => 'Mettre à jour le tag',
        ]);

        $resolver->setAllowedTypes('submit_label', 'string');
    }
}
