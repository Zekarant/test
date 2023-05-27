<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use App\Repository\NewsRepository;
use App\Model\TimestampedInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\File;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News implements TimestampedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titleNews = null;

    #[ORM\Column(length: 255)]
    private ?string $descriptionNews = null;

    #[ORM\Column(length: 255)]
    private ?string $contentNews = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $keywordsNews = null;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'news')]
    private Collection $categories;

    #[ORM\OneToMany(mappedBy: 'news', targetEntity: Comments::class, orphanRemoval: true)]
    #[OrderBy(["createdAt" => "DESC"])]
    private Collection $comments;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = 'default_image.png';

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'news')]
    private ?User $author = null;

    #[ORM\Column]
    private ?bool $visibility = true;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitleNews(): ?string
    {
        return $this->titleNews;
    }

    public function setTitleNews(string $titleNews): self
    {
        $this->titleNews = $titleNews;

        return $this;
    }

    public function getDescriptionNews(): ?string
    {
        return $this->descriptionNews;
    }

    public function setDescriptionNews(string $descriptionNews): self
    {
        $this->descriptionNews = $descriptionNews;

        return $this;
    }

    public function getContentNews(): ?string
    {
        return $this->contentNews;
    }

    public function setContentNews(string $contentNews): self
    {
        $this->contentNews = $contentNews;

        return $this;
    }

    public function getKeywordsNews(): ?string
    {
        return $this->keywordsNews;
    }

    public function setKeywordsNews(?string $keywordsNews): self
    {
        $this->keywordsNews = $keywordsNews;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
            $category->addNews($this);
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->categories->removeElement($category)) {
            $category->removeNews($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Comments>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comments $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setNews($this);
        }

        return $this;
    }

    public function removeComment(Comments $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getNews() === $this) {
                $comment->setNews(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage($image = null): self
    {
        if ($image != null) {
            $this->image = $image;
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function __toString(){
        return $this->titleNews ?? '';
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function isVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }
}
