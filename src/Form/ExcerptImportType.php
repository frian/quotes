<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExcerptImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'CSV à importer',
                'help' => 'Une ligne par extrait. Colonnes : artist, album, year, song, body, source_url, tags, note, position.',
                'attr' => [
                    'rows' => 16,
                    'placeholder' => "artist;album;year;song;body;source_url;tags;note;position\nDire Straits;Brothers in Arms;1985;Money for Nothing;I want my MTV...;https://example.com;rock, live;Note perso;1",
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('preview', SubmitType::class, [
                'label' => 'Prévisualiser',
            ])
            ->add('confirm', SubmitType::class, [
                'label' => 'Confirmer l’import',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
