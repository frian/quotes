<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(inversedBy: 'songs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Album $album = null;

    /**
     * @var Collection<int, SongExcerpt>
     */
    #[ORM\OneToMany(targetEntity: SongExcerpt::class, mappedBy: 'song', orphanRemoval: true)]
    private Collection $excerpts;

    public function __construct()
    {
        $this->excerpts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

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
            $excerpt->setSong($this);
        }

        return $this;
    }

    public function removeExcerpt(SongExcerpt $excerpt): static
    {
        if ($this->excerpts->removeElement($excerpt)) {
            if ($excerpt->getSong() === $this) {
                $excerpt->setSong(null);
            }
        }

        return $this;
    }
}
