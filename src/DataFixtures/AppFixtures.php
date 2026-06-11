<?php

namespace App\DataFixtures;

use App\Entity\Color;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /**
     * @var array<int, array{name: string, hexCode: string, class: string}>
     */
    private const COLORS = [
        ['name' => 'Jaune soleil', 'hexCode' => '#FFD166', 'class' => 'color-sun-yellow'],
        ['name' => 'Turquoise', 'hexCode' => '#6FDDDB', 'class' => 'color-turquoise'],
        ['name' => 'Orange vif', 'hexCode' => '#FFAB40', 'class' => 'color-bright-orange'],
        ['name' => 'Violet lilas', 'hexCode' => '#C9B1FF', 'class' => 'color-lilac-purple'],
        ['name' => 'Rose framboise', 'hexCode' => '#FFA8C5', 'class' => 'color-raspberry-pink'],
        ['name' => 'Vert émeraude', 'hexCode' => '#A8E6CF', 'class' => 'color-emerald-green'],
        ['name' => 'Abricot', 'hexCode' => '#FFD6A5', 'class' => 'color-apricot'],
        ['name' => 'Menthe franche', 'hexCode' => '#B5EAD7', 'class' => 'color-fresh-mint'],
        ['name' => 'Citron', 'hexCode' => '#FFFB87', 'class' => 'color-lemon'],
        ['name' => 'Bleu vif', 'hexCode' => '#96D4FF', 'class' => 'color-bright-blue'],
    ];

    public function load(ObjectManager $manager): void
    {
        $repository = $manager->getRepository(Color::class);

        foreach (self::COLORS as $colorData) {
            $color = $repository->findOneBy(['name' => $colorData['name']]) ?? new Color();

            $color
                ->setName($colorData['name'])
                ->setHexCode($colorData['hexCode'])
                ->setClass($colorData['class']);

            $manager->persist($color);
        }

        $manager->flush();
    }
}
