<?php

namespace App\Factory;

use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;

class OpenTelemetryResourceFactory
{
    public function create(
        string $serviceName,
        string $serviceVersion,
        string $environment
    ): ResourceInfo {
        return ResourceInfoFactory::defaultResource()->merge(
            ResourceInfo::create(Attributes::create([
                'service.name' => $serviceName,
                'service.version' => $serviceVersion,
                'deployment.environment' => $environment,
            ]))
        );
    }
}