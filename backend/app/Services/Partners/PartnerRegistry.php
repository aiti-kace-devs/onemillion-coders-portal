<?php

namespace App\Services\Partners;

use App\Services\Partners\Contracts\PartnerProgressDriver;
use App\Services\Partners\Generic\GenericProgressDriverFactory;
use App\Services\Partners\Startocode\StartocodeProgressDriver;
use App\Support\PartnerCodeNormalizer;
use App\Support\StartocodePartnerCode;
use InvalidArgumentException;

class PartnerRegistry
{
    /** @var array<string, PartnerProgressDriver> */
    private array $driversByCode = [];

    private ?StartocodeProgressDriver $startocodeDriver = null;

    /**
     * @param  array<int, PartnerProgressDriver>  $drivers  Bundled / custom drivers (take precedence over generic).
     */
    public function __construct(
        array $drivers,
        private readonly GenericProgressDriverFactory $genericFactory
    ) {
        foreach ($drivers as $driver) {
            if ($driver instanceof StartocodeProgressDriver) {
                $this->startocodeDriver = $driver;

                continue;
            }
            $code = PartnerCodeNormalizer::normalize($driver->code());
            if ($code === '') {
                continue;
            }
            $this->driversByCode[$code] = $driver;
        }

        if ($this->startocodeDriver !== null) {
            $this->driversByCode[StartocodePartnerCode::current()] = $this->startocodeDriver;
        }
    }

    public function has(string $partnerCode): bool
    {
        $code = PartnerCodeNormalizer::normalize($partnerCode);
        if ($code === '') {
            return false;
        }

        if ($this->startocodeDriver !== null && $code === StartocodePartnerCode::current()) {
            return true;
        }

        return array_key_exists($code, $this->driversByCode)
            || $this->genericFactory->supports($code);
    }

    public function get(string $partnerCode): PartnerProgressDriver
    {
        $code = PartnerCodeNormalizer::normalize($partnerCode);
        if ($code === '') {
            throw new InvalidArgumentException('partner_code cannot be empty.');
        }

        if ($this->startocodeDriver !== null && $code === StartocodePartnerCode::current()) {
            return $this->startocodeDriver;
        }

        if (array_key_exists($code, $this->driversByCode)) {
            return $this->driversByCode[$code];
        }

        if ($this->genericFactory->supports($code)) {
            return $this->genericFactory->make($code);
        }

        throw new InvalidArgumentException("No partner progress driver registered for partner_code='{$code}'.");
    }

    /**
     * Bundled drivers only (excludes generic integrations).
     *
     * @return array<string, PartnerProgressDriver>
     */
    public function registeredDrivers(): array
    {
        return $this->driversByCode;
    }

    /**
     * @return array<string, PartnerProgressDriver>
     */
    public function all(): array
    {
        $merged = $this->driversByCode;
        foreach ($this->genericFactory->enabledPartnerCodes() as $code) {
            if (! array_key_exists($code, $merged)) {
                $merged[$code] = $this->genericFactory->make($code);
            }
        }

        return $merged;
    }
}

