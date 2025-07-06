<?php

namespace Modules\Attendance\Services;

use Modules\Attendance\Contracts\DeviceConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Service for device-related attendance constraint validations.
 */
class DeviceConstraintService extends BaseConstraintService implements DeviceConstraintServiceInterface
{
    /**
     * Validate device constraints for attendance.
     * This is a dispatcher method that handles different types of device constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDeviceConstraint(Attendance $attendance, array $config): bool|array
    {
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';

        switch ($subtype) {
            // case AttendanceConstraint::DEVICE_WHITELIST:
            //     return $this->validateDeviceWhitelist($attendance, $config);

            // case AttendanceConstraint::DEVICE_BLACKLIST:
            //     return $this->validateDeviceBlacklist($attendance, $config);

            // case AttendanceConstraint::DEVICE_TYPE_RESTRICTION:
            //     return $this->validateDeviceTypeRestriction($attendance, $config);

            // case AttendanceConstraint::DEVICE_REGISTRATION:
            //     return $this->validateDeviceRegistration($attendance, $config);

            // case AttendanceConstraint::DEVICE_SECURITY_FEATURES:
            //     return $this->validateDeviceSecurityFeatures($attendance, $config);

            case AttendanceConstraint::DEVICE_AUTHORIZED_ONLY:
                return $this->validateAuthorizedOnly($attendance, $config);

            case AttendanceConstraint::DEVICE_FINGERPRINTING:
                return $this->validateFingerprinting($attendance, $config);

            case AttendanceConstraint::DEVICE_SINGLE_POLICY:
                return $this->validateSinglePolicy($attendance, $config);

            case AttendanceConstraint::DEVICE_APP_RESTRICTIONS:
                return $this->validateAppRestrictions($attendance, $config);

            case AttendanceConstraint::DEVICE_BROWSER_RESTRICTIONS:
                return $this->validateBrowserRestrictions($attendance, $config);

            default:
                return false;
        }
    }

    /**
     * Validate device whitelist constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDeviceWhitelist(Attendance $attendance, array $config): bool|array
    {
        // Check if device whitelist is enabled
        $whitelistEnabled = $config['whitelist_enabled'] ?? false;
        if (!$whitelistEnabled) {
            return false;
        }

        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $deviceId = $deviceInfo['device_id'] ?? $attendance->device_id ?? null;

        if (!$deviceId) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_WHITELIST,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device ID is required for whitelist validation but is missing.',
                'details' => [
                    'whitelist_enabled' => true,
                    'device_id_provided' => false
                ]
            ];
        }

        // Check against allowed devices
        $allowedDevices = $config['allowed_devices'] ?? [];

        if (!in_array($deviceId, $allowedDevices)) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_WHITELIST,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device is not in the allowed whitelist.',
                'details' => [
                    'device_id' => $deviceId,
                    'allowed_devices' => $allowedDevices
                ]
            ];
        }

        return false;
    }

    /**
     * Validate device blacklist constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDeviceBlacklist(Attendance $attendance, array $config): bool|array
    {
        // Check if device blacklist is enabled
        $blacklistEnabled = $config['blacklist_enabled'] ?? false;
        if (!$blacklistEnabled) {
            return false;
        }

        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $deviceId = $deviceInfo['device_id'] ?? $attendance->device_id ?? null;

        if (!$deviceId) {
            return false; // If no device ID, can't check blacklist
        }

        // Check against blocked devices
        $blockedDevices = $config['blocked_devices'] ?? [];

        if (in_array($deviceId, $blockedDevices)) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_BLACKLIST,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device is in the blocked blacklist.',
                'details' => [
                    'device_id' => $deviceId,
                    'blocked_devices' => $blockedDevices
                ]
            ];
        }

        return false;
    }

    /**
     * Validate device type restriction constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDeviceTypeRestriction(Attendance $attendance, array $config): bool|array
    {
        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $deviceType = $deviceInfo['device_type'] ?? $attendance->device_type ?? null;

        if (!$deviceType) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_TYPE_RESTRICTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device type information is required but is missing.',
                'details' => [
                    'device_type_provided' => false
                ]
            ];
        }

        // Check allowed device types
        if (isset($config['allowed_device_types']) && is_array($config['allowed_device_types'])) {
            if (!in_array($deviceType, $config['allowed_device_types'])) {
                return [
                    'constraint_type' => AttendanceConstraint::DEVICE_TYPE_RESTRICTION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Device type is not allowed.',
                    'details' => [
                        'device_type' => $deviceType,
                        'allowed_types' => $config['allowed_device_types']
                    ]
                ];
            }
        }

        // Check blocked device types
        if (isset($config['blocked_device_types']) && is_array($config['blocked_device_types'])) {
            if (in_array($deviceType, $config['blocked_device_types'])) {
                return [
                    'constraint_type' => AttendanceConstraint::DEVICE_TYPE_RESTRICTION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Device type is blocked.',
                    'details' => [
                        'device_type' => $deviceType,
                        'blocked_types' => $config['blocked_device_types']
                    ]
                ];
            }
        }

        // Check operating system restrictions
        if (isset($config['allowed_operating_systems']) && is_array($config['allowed_operating_systems'])) {
            $operatingSystem = $deviceInfo['operating_system'] ?? $attendance->operating_system ?? null;

            if (!$operatingSystem || !in_array($operatingSystem, $config['allowed_operating_systems'])) {
                return [
                    'constraint_type' => AttendanceConstraint::DEVICE_TYPE_RESTRICTION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Operating system is not allowed.',
                    'details' => [
                        'operating_system' => $operatingSystem,
                        'allowed_operating_systems' => $config['allowed_operating_systems']
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * Validate device registration constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDeviceRegistration(Attendance $attendance, array $config): bool|array
    {
        // Check if device registration is required
        $registrationRequired = $config['registration_required'] ?? false;
        if (!$registrationRequired) {
            return false;
        }

        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $deviceId = $deviceInfo['device_id'] ?? $attendance->device_id ?? null;

        if (!$deviceId) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_REGISTRATION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device ID is required for registration validation but is missing.',
                'details' => [
                    'registration_required' => true,
                    'device_id_provided' => false
                ]
            ];
        }

        // Check if device is registered
        $isRegistered = $attendance->device_registered ?? false;

        if (!$isRegistered) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_REGISTRATION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device is not registered.',
                'details' => [
                    'device_id' => $deviceId,
                    'registration_required' => true,
                    'is_registered' => false
                ]
            ];
        }

        // Check registration expiry
        if (isset($config['check_registration_expiry']) && $config['check_registration_expiry']) {
            $registrationExpiry = $attendance->device_registration_expiry ?? null;

            if ($registrationExpiry && \Carbon\Carbon::parse($registrationExpiry)->isPast()) {
                return [
                    'constraint_type' => AttendanceConstraint::DEVICE_REGISTRATION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Device registration has expired.',
                    'details' => [
                        'device_id' => $deviceId,
                        'registration_expiry' => $registrationExpiry,
                        'current_time' => now()->toISOString()
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * Validate device security features constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDeviceSecurityFeatures(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        $deviceInfo = $attendance->device_info ?? [];

        // Check screen lock requirement
        if (isset($config['screen_lock_required']) && $config['screen_lock_required']) {
            $hasScreenLock = $deviceInfo['has_screen_lock'] ?? $attendance->has_screen_lock ?? false;

            if (!$hasScreenLock) {
                $violations[] = [
                    'type' => 'missing_screen_lock',
                    'message' => 'Device screen lock is required but is not enabled'
                ];
            }
        }

        // Check encryption requirement
        if (isset($config['encryption_required']) && $config['encryption_required']) {
            $isEncrypted = $deviceInfo['is_encrypted'] ?? $attendance->device_encrypted ?? false;

            if (!$isEncrypted) {
                $violations[] = [
                    'type' => 'missing_encryption',
                    'message' => 'Device encryption is required but is not enabled'
                ];
            }
        }

        // Check antivirus requirement
        if (isset($config['antivirus_required']) && $config['antivirus_required']) {
            $hasAntivirus = $deviceInfo['has_antivirus'] ?? $attendance->has_antivirus ?? false;

            if (!$hasAntivirus) {
                $violations[] = [
                    'type' => 'missing_antivirus',
                    'message' => 'Antivirus software is required but is not installed'
                ];
            }
        }

        // Check OS version requirement
        if (isset($config['min_os_version'])) {
            $osVersion = $deviceInfo['os_version'] ?? $attendance->os_version ?? null;
            $minOsVersion = $config['min_os_version'];

            if (!$osVersion || version_compare($osVersion, $minOsVersion, '<')) {
                $violations[] = [
                    'type' => 'outdated_os_version',
                    'message' => "Operating system version is outdated. Required: {$minOsVersion}, Current: {$osVersion}",
                    'required_version' => $minOsVersion,
                    'current_version' => $osVersion
                ];
            }
        }

        // Check app version requirement
        if (isset($config['min_app_version'])) {
            $appVersion = $deviceInfo['app_version'] ?? $attendance->app_version ?? null;
            $minAppVersion = $config['min_app_version'];

            if (!$appVersion || version_compare($appVersion, $minAppVersion, '<')) {
                $violations[] = [
                    'type' => 'outdated_app_version',
                    'message' => "Application version is outdated. Required: {$minAppVersion}, Current: {$appVersion}",
                    'required_version' => $minAppVersion,
                    'current_version' => $appVersion
                ];
            }
        }

        // Check jailbreak/root detection
        if (isset($config['block_rooted_devices']) && $config['block_rooted_devices']) {
            $isRooted = $deviceInfo['is_rooted'] ?? $attendance->device_rooted ?? false;

            if ($isRooted) {
                $violations[] = [
                    'type' => 'rooted_device',
                    'message' => 'Rooted/jailbroken devices are not allowed'
                ];
            }
        }

        // Check VPN detection
        if (isset($config['block_vpn_usage']) && $config['block_vpn_usage']) {
            $isUsingVpn = $deviceInfo['is_using_vpn'] ?? $attendance->using_vpn ?? false;

            if ($isUsingVpn) {
                $violations[] = [
                    'type' => 'vpn_usage',
                    'message' => 'VPN usage is not allowed'
                ];
            }
        }

        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_SECURITY_FEATURES,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device security requirements not met.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }

        return false;
    }

    /**
     * Validate authorized device constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateAuthorizedOnly(Attendance $attendance, array $config): bool|array
    {
        // Check if authorized only is enabled
        $authorizedOnlyEnabled = $config['authorized_only_enabled'] ?? false;
        if (!$authorizedOnlyEnabled) {
            return false;
        }

        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $deviceId = $deviceInfo['device_id'] ?? $attendance->device_id ?? null;

        if (!$deviceId) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_AUTHORIZED_ONLY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device ID is required for authorized device validation.',
                'details' => []
            ];
        }

        // Check if device is authorized
        $authorizedDevices = $config['authorized_devices'] ?? [];
        if (!in_array($deviceId, $authorizedDevices)) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_AUTHORIZED_ONLY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device is not authorized for attendance.',
                'details' => [
                    'device_id' => $deviceId,
                    'authorized_devices' => $authorizedDevices
                ]
            ];
        }

        return false;
    }

    /**
     * Validate device fingerprinting constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateFingerprinting(Attendance $attendance, array $config): bool|array
    {
        // Check if fingerprinting is enabled
        $fingerprintingEnabled = $config['fingerprinting_enabled'] ?? false;
        if (!$fingerprintingEnabled) {
            return false;
        }

        // Get device fingerprint
        $deviceInfo = $attendance->device_info ?? [];
        $fingerprint = $deviceInfo['fingerprint'] ?? null;

        if (!$fingerprint) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_FINGERPRINTING,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device fingerprint is required.',
                'details' => []
            ];
        }

        // Validate fingerprint format and requirements
        $requiredFingerprint = $config['required_fingerprint'] ?? null;
        if ($requiredFingerprint && $fingerprint !== $requiredFingerprint) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_FINGERPRINTING,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device fingerprint does not match required fingerprint.',
                'details' => [
                    'provided_fingerprint' => $fingerprint,
                    'required_fingerprint' => $requiredFingerprint
                ]
            ];
        }

        return false;
    }

    /**
     * Validate single policy constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateSinglePolicy(Attendance $attendance, array $config): bool|array
    {
        // Check if single policy is enabled
        $singlePolicyEnabled = $config['single_policy_enabled'] ?? false;
        if (!$singlePolicyEnabled) {
            return false;
        }

        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $deviceId = $deviceInfo['device_id'] ?? $attendance->device_id ?? null;
        $userId = $attendance->user_id;

        if (!$deviceId) {
            return false;
        }

        // Check if another user is currently using this device
        $activeAttendance = Attendance::where('device_id', $deviceId)
            ->where('user_id', '!=', $userId)
            ->whereNull('clock_out_time')
            ->where('attendance_date', $attendance->attendance_date)
            ->first();

        if ($activeAttendance) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_SINGLE_POLICY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Device is currently being used by another user.',
                'details' => [
                    'device_id' => $deviceId,
                    'current_user_id' => $activeAttendance->user_id,
                    'current_user' => $activeAttendance->user->name ?? 'Unknown'
                ]
            ];
        }

        return false;
    }

    /**
     * Validate app restrictions constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateAppRestrictions(Attendance $attendance, array $config): bool|array
    {
        // Check if app restrictions are enabled
        $appRestrictionsEnabled = $config['app_restrictions_enabled'] ?? false;
        if (!$appRestrictionsEnabled) {
            return false;
        }

        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $appName = $deviceInfo['app_name'] ?? $deviceInfo['application'] ?? null;

        if (!$appName) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_APP_RESTRICTIONS,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Application name is required for app restrictions validation.',
                'details' => []
            ];
        }

        // Check allowed apps
        $allowedApps = $config['allowed_apps'] ?? [];
        if (!empty($allowedApps) && !in_array($appName, $allowedApps)) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_APP_RESTRICTIONS,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Application is not in the allowed apps list.',
                'details' => [
                    'app_name' => $appName,
                    'allowed_apps' => $allowedApps
                ]
            ];
        }

        // Check blocked apps
        $blockedApps = $config['blocked_apps'] ?? [];
        if (!empty($blockedApps) && in_array($appName, $blockedApps)) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_APP_RESTRICTIONS,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Application is in the blocked apps list.',
                'details' => [
                    'app_name' => $appName,
                    'blocked_apps' => $blockedApps
                ]
            ];
        }

        return false;
    }

    /**
     * Validate browser restrictions constraints.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBrowserRestrictions(Attendance $attendance, array $config): bool|array
    {
        // Check if browser restrictions are enabled
        $browserRestrictionsEnabled = $config['browser_restrictions_enabled'] ?? false;
        if (!$browserRestrictionsEnabled) {
            return false;
        }

        // Get device information
        $deviceInfo = $attendance->device_info ?? [];
        $browserName = $deviceInfo['browser'] ?? $deviceInfo['user_agent'] ?? null;

        if (!$browserName) {
            return [
                'constraint_type' => AttendanceConstraint::DEVICE_BROWSER_RESTRICTIONS,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Browser information is required for browser restrictions validation.',
                'details' => []
            ];
        }

        // Check allowed browsers
        $allowedBrowsers = $config['allowed_browsers'] ?? [];
        if (!empty($allowedBrowsers)) {
            $browserAllowed = false;
            foreach ($allowedBrowsers as $allowedBrowser) {
                if (stripos($browserName, $allowedBrowser) !== false) {
                    $browserAllowed = true;
                    break;
                }
            }

            if (!$browserAllowed) {
                return [
                    'constraint_type' => AttendanceConstraint::DEVICE_BROWSER_RESTRICTIONS,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Browser is not in the allowed browsers list.',
                    'details' => [
                        'browser_name' => $browserName,
                        'allowed_browsers' => $allowedBrowsers
                    ]
                ];
            }
        }

        // Check blocked browsers
        $blockedBrowsers = $config['blocked_browsers'] ?? [];
        if (!empty($blockedBrowsers)) {
            foreach ($blockedBrowsers as $blockedBrowser) {
                if (stripos($browserName, $blockedBrowser) !== false) {
                    return [
                        'constraint_type' => AttendanceConstraint::DEVICE_BROWSER_RESTRICTIONS,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'Browser is in the blocked browsers list.',
                        'details' => [
                            'browser_name' => $browserName,
                            'blocked_browsers' => $blockedBrowsers
                        ]
                    ];
                }
            }
        }

        return false;
    }
}
