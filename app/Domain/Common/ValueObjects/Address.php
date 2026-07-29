<?php

declare(strict_types=1);

namespace App\Domain\Common\ValueObjects;

final readonly class Address
{
    public function __construct(
        private string $street,
        private string $city,
        private string $state,
        private string $postalCode,
        private string $country,
    ) {}

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
        ];
    }

    public function __toString(): string
    {
        return sprintf('%s, %s, %s %s, %s', $this->street, $this->city, $this->state, $this->postalCode, $this->country);
    }
}
