<?php

declare(strict_types=1);

namespace Modules\PaymentGateway\Actions;

use Modules\PaymentGateway\DTOs\ConfigureGatewayDTO;
use Modules\PaymentGateway\Models\PaymentGateway;

/**
 * Action to configure a payment gateway
 */
class ConfigureGatewayAction
{
    public function execute(ConfigureGatewayDTO $dto, ?int $createdBy = null): PaymentGateway
    {
        // Check if gateway already exists
        $gateway = PaymentGateway::where('name', $dto->name)->first();

        if ($gateway) {
            // Update existing gateway
            if ($dto->displayName !== null) {
                $gateway->display_name = $dto->displayName;
            }
            
            // Handle is_active and is_test_mode with mutual exclusivity
            if ($dto->isActive !== null) {
                $gateway->is_active = $dto->isActive;
                // If activating, disable test mode
                if ($dto->isActive && $gateway->is_test_mode) {
                    $gateway->is_test_mode = false;
                }
            }
            if ($dto->isTestMode !== null) {
                $gateway->is_test_mode = $dto->isTestMode;
                // If enabling test mode, deactivate
                if ($dto->isTestMode && $gateway->is_active) {
                    $gateway->is_active = false;
                }
            }
            
            // Merge credentials - only update fields that are provided
            if ($dto->credentials !== null && !empty($dto->credentials)) {
                $existingCredentials = $gateway->credentials ?? [];
                $gateway->credentials = array_merge($existingCredentials, $dto->credentials);
            }
            
            if ($dto->settings !== null) {
                $gateway->settings = $dto->settings;
            }
            if ($dto->description !== null) {
                $gateway->description = $dto->description;
            }
            $gateway->save();
        } else {
            // Create new gateway
            $gateway = new PaymentGateway();
            $gateway->name = $dto->name;
            $gateway->display_name = $dto->displayName;
            
            // Ensure mutual exclusivity: cannot be both active and in test mode
            $isActive = $dto->isActive ?? false;
            $isTestMode = $dto->isTestMode ?? true;
            
            if ($isActive && $isTestMode) {
                // If both are true, prioritize test mode (safer default)
                $isActive = false;
            }
            
            $gateway->is_active = $isActive;
            $gateway->is_test_mode = $isTestMode;
            $gateway->credentials = $dto->credentials ?? [];
            $gateway->settings = $dto->settings;
            $gateway->description = $dto->description;
            $gateway->created_by = $createdBy;
            $gateway->save();
        }

        return $gateway;
    }
}
