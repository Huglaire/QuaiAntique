<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'statistics')]
class Statistics
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(type: 'date')]
    private ?\DateTime $generatedAt = null;

    #[ODM\Field(type: 'float')]
    private ?float $averageGuestsPerBooking = null;

    #[ODM\Field(type: 'hash')]
    private array $averageOccupancyByDay = [];

    #[ODM\Field(type: 'hash')]
    private array $busiestTimeSlot = [];

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getGeneratedAt(): ?\DateTime
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(
        \DateTime $generatedAt
    ): static {
        $this->generatedAt = $generatedAt;

        return $this;
    }

    public function getAverageGuestsPerBooking(): ?float
    {
        return $this->averageGuestsPerBooking;
    }

    public function setAverageGuestsPerBooking(
        float $averageGuestsPerBooking
    ): static {
        $this->averageGuestsPerBooking =
            $averageGuestsPerBooking;

        return $this;
    }

    public function getAverageOccupancyByDay(): array
    {
        return $this->averageOccupancyByDay;
    }

    public function setAverageOccupancyByDay(
        array $averageOccupancyByDay
    ): static {
        $this->averageOccupancyByDay =
            $averageOccupancyByDay;

        return $this;
    }

    public function getBusiestTimeSlot(): array
    {
        return $this->busiestTimeSlot;
    }

    public function setBusiestTimeSlot(
        array $busiestTimeSlot
    ): static {
        $this->busiestTimeSlot =
            $busiestTimeSlot;

        return $this;
    }
}