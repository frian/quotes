<?php

namespace App\Form;

use App\Entity\Album;
use App\Entity\Artist;
use App\Entity\Tag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class SongExcerptType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('artist', EntityType::class, [
                'label' => 'Artiste ou groupe',
                'class' => Artist::class,
                'choices' => $options['artists'],
                'choice_label' => 'name',
                'placeholder' => 'Choisir un artiste existant',
                'required' => false,
                'attr' => [
                    'data-excerpt-form-target' => 'artistInput',
                ],
                'row_attr' => [
                    'data-excerpt-form-target' => 'artistRow',
                ],
            ])
            ->add('newArtistName', TextType::class, [
                'label' => 'Autre artiste ou groupe',
                'required' => false,
                'help' => 'Les variantes déjà enregistrées sont réutilisées automatiquement.',
                'row_attr' => [
                    'data-excerpt-form-target' => 'newArtistRow',
                ],
            ])
            ->add('album', EntityType::class, [
                'label' => 'Album',
                'class' => Album::class,
                'choices' => $options['albums'],
                'choice_label' => static function (Album $album): string {
                    $artistName = $album->getArtist()?->getName();

                    return sprintf('%s - %s (%d)', $artistName, $album->getTitle(), $album->getReleaseYear());
                },
                'placeholder' => 'Choisir un album existant',
                'required' => false,
                'attr' => [
                    'data-excerpt-form-target' => 'albumSelect',
                    'data-action' => 'change->excerpt-form#sync input->excerpt-form#sync',
                ],
                'row_attr' => [
                    'data-excerpt-form-target' => 'albumRow',
                ],
            ])
            ->add('newAlbumTitle', TextType::class, [
                'label' => 'Autre album',
                'required' => false,
                'help' => 'Même artiste + titre + année = album existant.',
                'row_attr' => [
                    'data-excerpt-form-target' => 'newAlbumRow',
                ],
            ])
            ->add('releaseYear', IntegerType::class, [
                'label' => 'Année du nouvel album',
                'required' => false,
                'constraints' => [
                    new Range(min: 1900, max: 2100),
                ],
                'row_attr' => [
                    'data-excerpt-form-target' => 'releaseYearRow',
                ],
            ])
            ->add('songTitle', TextType::class, [
                'label' => 'Chanson',
                'help' => 'La chanson est réutilisée si elle existe déjà dans cet album.',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('songSourceUrl', UrlType::class, [
                'label' => 'Lien vers la chanson',
                'required' => false,
                'default_protocol' => 'https',
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
            ->add('tags', EntityType::class, [
                'label' => 'Tags',
                'class' => Tag::class,
                'choices' => $options['tags'],
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('newTagNames', TextType::class, [
                'label' => 'Ajouter tag',
                'required' => false,
                'help' => 'Tu peux ajouter plusieurs tags avec des virgules.',
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
            'albums' => [],
            'artists' => [],
            'data_class' => null,
            'submit_label' => 'Ajouter l’extrait',
            'tags' => [],
        ]);

        $resolver->setAllowedTypes('albums', 'array');
        $resolver->setAllowedTypes('artists', 'array');
        $resolver->setAllowedTypes('submit_label', 'string');
        $resolver->setAllowedTypes('tags', 'array');
    }
}
