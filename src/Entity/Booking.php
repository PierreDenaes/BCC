<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $bookAt = null;

    #[ORM\Column(type: "datetime", options: ["default" => "CURRENT_TIMESTAMP"])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $period = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Profile $profile = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\OneToMany(targetEntity: Participants::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participants;

    #[ORM\Column]
    private ?bool $isPaid = false;

    #[ORM\OneToOne(targetEntity: Invoice::class, mappedBy: 'booking', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Invoice $invoice = null;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'booking', cascade: ['remove'], orphanRemoval: true)]
    private Collection $notifications;
    #[ORM\Column]
    private ?int $nbrParticipant = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->participants = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookAt(): ?\DateTimeInterface
    {
        return $this->bookAt;
    }

    public function setBookAt(\DateTimeInterface $bookAt): static
    {
        $this->bookAt = $bookAt;

        return $this;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(?string $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @return Collection<int, Participants>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participants $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setBooking($this);
        }

        return $this;
    }

    public function removeParticipant(Participants $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getBooking() === $this) {
                $participant->setBooking(null);
            }
        }

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setIsPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }
    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        // unset the owning side of the relation if necessary
        if ($invoice === null && $this->invoice !== null) {
            $this->invoice->setBooking(null);
        }

        // set the owning side of the relation if necessary
        if ($invoice !== null && $invoice->getBooking() !== $this) {
            $invoice->setBooking($this);
        }

        $this->invoice = $invoice;

        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getNbrParticipant(): ?int
    {
        return $this->nbrParticipant;
    }

    public function setNbrParticipant(int $nbrParticipant): static
    {
        $this->nbrParticipant = $nbrParticipant;

        return $this;
    }
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setBooking($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // Set the owning side to null (unless already changed)
            if ($notification->getBooking() === $this) {
                $notification->setBooking(null);
            }
        }

        return $this;
    }
    public function __toString(): string
    {
        return $this->bookAt->format('d/m/Y') . ' - ' . $this->product . ' - ' . $this->profile;
    }
}
