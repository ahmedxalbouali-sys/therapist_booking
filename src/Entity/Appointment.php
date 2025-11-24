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

    // ---------- Getters and Setters ----------

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

    public function getEndAt(): ?\DateTime
    {
        return $this->startAt ? (clone $this->startAt)->modify('+1 hour') : null;
    }

    public function getStatus(): string
    {
        $now = new \DateTime();
        $endAt = $this->getEndAt();

        if (!$this->startAt) {
            return 'scheduled';
        }

        if ($now < $this->startAt) {
            return 'scheduled';
        } elseif ($now >= $this->startAt && $now < $endAt) {
            return 'in_progress';
        }

        return 'completed';
    }

    public function updateStatus(): void
    {
        $this->status = $this->getStatus();
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

    // ---------- Collision Helper ----------

    public function overlapsWith(Appointment $other): bool
    {
        if (!$this->startAt || !$other->getStartAt()) {
            return false;
        }

        if ($this->therapist?->getId() !== $other->getTherapist()?->getId()) {
            return false; // different therapist
        }

        $startA = $this->startAt;
        $endA = $this->getEndAt();

        $startB = $other->getStartAt();
        $endB = $other->getEndAt();

        return $startA < $endB && $endA > $startB;
    }
}
