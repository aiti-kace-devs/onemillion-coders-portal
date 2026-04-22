<?php

namespace App\Integrations\Partners;

use App\Models\Partner;
use App\Models\Programme;
use Exception;

class PartnerManager
{
    /**
     * Resolve the integration instance for a given programme or partner.
     */
    public function resolve(Programme|Partner $entity): PartnerIntegrationInterface
    {
        $partner = $entity instanceof Partner ? $entity : $entity->partner;

        if (!$partner) {
            throw new Exception("Entity is not associated with a partner.");
        }

        $className = $this->getIntegrationClassName($partner->slug);

        if (!class_exists($className)) {
            throw new Exception("Integration class for '{$partner->slug}' not found: {$className}");
        }

        return new $className($partner);
    }

    /**
     * Map partner slug to integration class name.
     */
    protected function getIntegrationClassName(string $slug): string
    {
        // Example: 'startocode' -> 'App\Integrations\Partners\StartocodeIntegration'
        $name = str($slug)->studly()->finish('Integration');
        return "App\\Integrations\\Partners\\{$name}";
    }
}
