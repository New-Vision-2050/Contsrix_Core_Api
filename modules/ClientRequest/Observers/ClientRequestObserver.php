<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Observers;

use Modules\ClientRequest\Models\ClientRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientRequestObserver
{
    /**
     * Handle the ClientRequest "creating" event.
     */
    public function creating(ClientRequest $clientRequest): void
    {
        // Generate serial number only if it's not already set
        if (is_null($clientRequest->serial_number)) {
            $clientRequest->serial_number = $this->generateSerialNumber($clientRequest->company_id);
        }

        // Set created_by_user_id from authenticated user if not already set
        if (is_null($clientRequest->created_by_user_id) && Auth::check()) {
            $clientRequest->created_by_user_id = (string) Auth::id();
        }
    }

    /**
     * Generate a unique serial number for the client request.
     * Format: REQ-{company_code}-{number} (e.g., REQ-COMP1-0001)
     */
    private function generateSerialNumber(string $companyId): string
    {
        // Get the maximum serial number for this company
        $maxSerial = DB::table('client_requests')
            ->where('company_id', $companyId)
            ->where('serial_number', 'like', 'REQ-%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(serial_number, \'-\', -1) AS UNSIGNED) DESC')
            ->value('serial_number');

        if ($maxSerial) {
            // Extract numeric part (after last dash) and increment
            $parts = explode('-', $maxSerial);
            $numericPart = (int) end($parts);
            $newNumeric = $numericPart + 1;
        } else {
            $newNumeric = 1;
        }

        // Create a short company code from the company ID (first 4 chars)
        $companyCode = strtoupper(substr($companyId, 0, 4));
        
        // Format with leading zeros (4 digits)
        return 'REQ-' . $companyCode . '-' . str_pad((string)$newNumeric, 4, '0', STR_PAD_LEFT);
    }
}
