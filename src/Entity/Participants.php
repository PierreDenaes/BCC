<?php

namespace App\Entity;

use App\Repository\ParticipantsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipantsRepository::class)]
class Participants
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Booking::class, inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Booking $booking = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isNotified = null;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
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
    public function __toString(): string
    {
        return $this->name . ' ' . $this->email;
    }

    public function isNotified(): ?bool
    {
        return $this->isNotified;
    }

    public function setIsNotified(?bool $isNotified): static
    {
        $this->isNotified = $isNotified;

        return $this;
    }
}
