<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $startAt = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'scheduled';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?User $client = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    private ?Therapist $therapist = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartAt(): ?\DateTime
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTime $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }


    public function updateStatus(): void
    {
        $now = new \DateTime();
        $endAt = clone $this->startAt;
        $endAt->modify('+1 hour');

        if ($now < $this->startAt) {
            $this->status = 'scheduled';
        } elseif ($now >= $this->startAt && $now < $endAt) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'completed';
        }
    }      


    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getTherapist(): ?Therapist
    {
        return $this->therapist;
    }

    public function setTherapist(?Therapist $therapist): static
    {
        $this->therapist = $therapist;

        return $this;
    }
}
