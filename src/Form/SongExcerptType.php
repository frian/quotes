<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class SongExcerptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('artistName', TextType::class, [
                'label' => 'Artiste ou groupe',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('albumTitle', TextType::class, [
                'label' => 'Album',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('releaseYear', IntegerType::class, [
                'label' => 'Année',
                'constraints' => [
                    new NotBlank(),
                    new Range(min: 1900, max: 2100),
                ],
            ])
            ->add('songTitle', TextType::class, [
                'label' => 'Chanson',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Extrait',
                'attr' => [
                    'rows' => 8,
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'required' => false,
            ])
            ->add('tagNames', TextType::class, [
                'label' => 'Tags',
                'required' => false,
                'help' => 'Sépare les tags par des virgules.',
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note personnelle',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => $options['submit_label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'submit_label' => 'Ajouter l’extrait',
        ]);

        $resolver->setAllowedTypes('submit_label', 'string');
    }
}
