<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_tag_name', fields: ['name'])]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Color::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Color $color = null;

    /**
     * @var Collection<int, SongExcerpt>
     */
    #[ORM\ManyToMany(targetEntity: SongExcerpt::class, mappedBy: 'tags')]
    private Collection $excerpts;

    public function __construct()
    {
        $this->excerpts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, SongExcerpt>
     */
    public function getExcerpts(): Collection
    {
        return $this->excerpts;
    }

    public function addExcerpt(SongExcerpt $excerpt): static
    {
        if (!$this->excerpts->contains($excerpt)) {
            $this->excerpts->add($excerpt);
            $excerpt->addTag($this);
        }

        return $this;
    }

    public function removeExcerpt(SongExcerpt $excerpt): static
    {
        if ($this->excerpts->removeElement($excerpt)) {
            $excerpt->removeTag($this);
        }

        return $this;
    }
}
