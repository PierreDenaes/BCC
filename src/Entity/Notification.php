<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[Vich\Uploadable]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Booking::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Booking $booking = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    // Nouveau champ pour le stockage du nom du fichier
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $pdfFilename = null;

    // Champ non persisté pour gérer l'upload avec VichUploader
    #[Vich\UploadableField(mapping: "notification_pdfs", fileNameProperty: "pdfFilename")]
    private ?File $pdfFile = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isRead = false;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getRecipient(): ?Profile
    {
        return $this->booking ? $this->booking->getProfile() : null;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function getPdfFilename(): ?string
    {
        return $this->pdfFilename;
    }

    public function setPdfFilename(?string $pdfFilename): self
    {
        $this->pdfFilename = $pdfFilename;
        return $this;
    }

    public function getPdfFile(): ?File
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?File $pdfFile = null): void
    {
        $this->pdfFile = $pdfFile;
        if ($pdfFile) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;
        return $this;
    }
    public function __sleep()
    {
        return ['id', 'title', 'message', 'createdAt','booking', 'isRead', 'pdfFilename']; // Exclut pdfFile
    }

    public function __wakeup()
    {
        $this->pdfFile = null; // Empêche les erreurs de désérialisation
    }
}
