<?php

declare(strict_types=1);

namespace Modules\UserManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\UserManagement\Models\Permission;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\User;

/**
 * Seeder for UserManagement module
 * Creates demo users, roles, and permissions
 */
class UserManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedUsers();
    }

    /**
     * Seed permissions from Core module config
     */
    private function seedPermissions(): void
    {
        // Get permissions from Core module config
        $permissionsPath = base_path('Modules/Core/config/permissions.php');
        $permissions = file_exists($permissionsPath) ? require $permissionsPath : [];

        foreach ($permissions as $slug => $description) {
            Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => ucwords(str_replace('_', ' ', $slug)),
                    'description' => $description,
                    'module' => $this->getModuleFromPermission($slug),
                ]
            );
        }

        $this->command->info('Permissions seeded successfully.');
    }

    /**
     * Seed roles with hierarchy
     */
    private function seedRoles(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'Full system access with all permissions',
                'hierarchy' => 100,
                'is_system' => true, // Only Super Admin is a system role
                'permissions' => [], // All permissions
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access to manage users and content',
                'hierarchy' => 80,
                'is_system' => false,
                'permissions' => [
                    // User Management
                    'view_users',
                    'create_user',
                    'update_user',
                    'delete_user',
                    'manage_users',
                    // Role & Permission
                    'view_roles',
                    'create_role',
                    'update_role',
                    'delete_role',
                    'manage_roles',
                    'view_permissions',
                    'create_permission',
                    'update_permission',
                    'delete_permission',
                    'manage_permissions',
                    // Dashboard
                    'view_dashboard',
                    'view_analytics',
                    // General Settings
                    'view_general_settings',
                    'update_general_settings',
                    'manage_general_settings',
                    // Auth Settings
                    'view_auth_settings',
                    'update_auth_settings',
                    'manage_auth_settings',
                    // Client Settings
                    'view_clients',
                    'create_client',
                    'update_client',
                    'delete_client',
                    'manage_clients',
                    'generate_api_keys',
                    'revoke_api_keys',
                    'rotate_api_keys',
                    'view_api_key_activity',
                    // Email Configuration
                    'view_email_config',
                    'update_email_config',
                    'manage_email_config',
                    'view_email_templates',
                    'create_email_template',
                    'update_email_template',
                    'delete_email_template',
                    'manage_email_templates',
                    'send_test_email',
                    // Storage Management
                    'view_storage_config',
                    'update_storage_config',
                    'manage_storage_config',
                    'view_storage_analytics',
                    'migrate_storage',
                    'cleanup_storage',
                    'upload_files',
                    'bulk_upload_files',
                    'delete_files',
                    'view_files',
                    // Logs & Activity
                    'view_logs',
                    'manage_logs',
                    'view_activity',
                    // AI Integration
                    'manage_ai_providers',
                    'manage_ai_api_keys',
                    'view_ai_usage',
                    'manage_prompt_templates',
                    // Notifications
                    'view_notification_settings',
                    'manage_notification_settings',
                    // Payment Gateway
                    'view_payment_gateways',
                    'manage_payment_gateways',
                    'view_invoice_templates',
                    'manage_invoice_templates',
                    // Footage Library (admin full access)
                    'upload_footage',
                    'bulk_upload_footage',
                    'view_footage_library',
                    'manage_footage_library',
                    'manage_footage_folders',
                    'view_all_footage',
                    'view_footage_stats',
                    'delete_any_footage',
                    // Audio Library (admin full access)
                    'upload_audio',
                    'bulk_upload_audio',
                    'view_audio_library',
                    'manage_audio_library',
                    'manage_audio_folders',
                    'view_all_audio',
                    'view_audio_stats',
                    'delete_any_audio',
                    // Image Library (admin full access)
                    'upload_image',
                    'bulk_upload_image',
                    'view_image_library',
                    'manage_image_library',
                    'manage_image_folders',
                    'view_all_image',
                    'view_image_stats',
                    'delete_any_image',
                    // BGM Library (admin full access)
                    'upload_bgm',
                    'bulk_upload_bgm',
                    'manage_bgm_folders',
                    'view_all_bgm',
                    'view_bgm_stats',
                    'delete_any_bgm',
                    // Video Overlay (admin full access)
                    'upload_video_overlay',
                    'manage_video_overlay_folders',
                    'view_all_video_overlay',
                    'view_video_overlay_stats',
                    'delete_any_video_overlay',
                    // Image Overlay (admin full access)
                    'upload_image_overlay',
                    'bulk_upload_image_overlay',
                    'manage_image_overlay_folders',
                    'view_all_image_overlay',
                    'view_image_overlay_stats',
                    'delete_any_image_overlay',
                    // Social Connection
                    'manage_social_connection_providers',
                    // Support Tickets
                    'view_all_tickets',
                    'manage_tickets',
                    'assign_tickets',
                ],
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manager with content and team management access',
                'hierarchy' => 60,
                'is_system' => false,
                'permissions' => [
                    'view_users',
                    'view_dashboard',
                    'view_analytics',
                    'view_general_settings',
                    'view_files',
                    'upload_files',
                ],
            ],
            [
                'name' => 'Customer',
                'slug' => 'customer',
                'description' => 'Standard customer access',
                'hierarchy' => 20,
                'is_system' => false,
                'permissions' => [
                    'view_own_profile',
                    'edit_own_profile',
                    'call_ai_models',
                    'use_prompt_templates',
                    // 'use_ai_chat',
                    // Library & Overlay – browse shared (read-only)
                    'use_audio_library',
                    'use_image_library',
                    'use_footage_library',
                    'use_bgm_library',
                    'use_video_overlay',
                    'use_image_overlay',
                ],
            ],
            [
                'name' => 'Guest',
                'slug' => 'guest',
                'description' => 'Limited guest access',
                'hierarchy' => 0,
                'is_system' => false,
                'permissions' => [],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            // Create or update role
            $role = Role::withTrashed()->updateOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'hierarchy' => $roleData['hierarchy'],
                    'is_system' => $roleData['is_system'],
                ]
            );

            if ($role->trashed()) {
                $role->restore();
            }

            // Assign permissions
            if (!empty($permissions)) {
                $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();
                $role->permissions()->syncWithoutDetaching($permissionIds);
            } elseif ($roleData['slug'] === 'super_admin') {
                // Super admin gets all permissions
                $role->permissions()->syncWithoutDetaching(Permission::pluck('id')->toArray());
            }
        }

        $this->command->info('Roles seeded successfully.');
    }

    /**
     * Seed demo users
     */
    private function seedUsers(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => 'Password123!',
                'status' => User::STATUS_ACTIVE,
                'is_system' => true, // System user - cannot be deleted
                'email_verified_at' => now(),
                'roles' => ['super_admin'],
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => 'Password123!',
                'status' => User::STATUS_ACTIVE,
                'is_system' => false,
                'email_verified_at' => now(),
                'roles' => ['admin'],
            ],
            [
                'name' => 'Manager User',
                'email' => 'manager@example.com',
                'password' => 'Password123!',
                'status' => User::STATUS_ACTIVE,
                'is_system' => false,
                'email_verified_at' => now(),
                'roles' => ['manager'],
            ],
            [
                'name' => 'John Doe',
                'email' => 'customer@example.com',
                'password' => 'Password123!',
                'status' => User::STATUS_ACTIVE,
                'is_system' => false,
                'email_verified_at' => now(),
                'roles' => ['customer'],
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => 'Password123!',
                'status' => User::STATUS_ACTIVE,
                'is_system' => false,
                'email_verified_at' => now(),
                'roles' => ['customer'],
            ],
            [
                'name' => 'Suspended User',
                'email' => 'suspended@example.com',
                'password' => 'Password123!',
                'status' => User::STATUS_SUSPENDED,
                'is_system' => false,
                'email_verified_at' => now(),
                'roles' => ['customer'],
            ],
        ];

        foreach ($users as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);

            // Create user if not exists
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'status' => $userData['status'],
                    'is_system' => $userData['is_system'],
                    'email_verified_at' => $userData['email_verified_at'],
                ]
            );

            // Assign roles
            $roleIds = Role::whereIn('slug', $roles)->pluck('id')->toArray();
            $user->roles()->syncWithoutDetaching($roleIds);
        }

        $this->command->info('Demo users seeded successfully.');
        $this->command->info('Default password for all users: Password123!');
    }

    /**
     * Determine module from permission slug
     */
    private function getModuleFromPermission(string $slug): ?string
    {
        // User Management
        if (in_array($slug, ['view_users', 'create_user', 'update_user', 'delete_user', 'manage_users'])) {
            return 'User Management';
        }

        // Role & Permission Management
        if (
            in_array($slug, [
                'view_roles',
                'create_role',
                'update_role',
                'delete_role',
                'manage_roles',
                'view_permissions',
                'create_permission',
                'update_permission',
                'delete_permission',
                'manage_permissions'
            ])
        ) {
            return 'Role & Permission';
        }

        // Dashboard & Analytics
        if (in_array($slug, ['view_dashboard', 'view_analytics'])) {
            return 'Dashboard';
        }

        // General Settings
        if (in_array($slug, ['view_general_settings', 'update_general_settings', 'manage_general_settings'])) {
            return 'General Settings';
        }

        // Authentication Settings
        if (in_array($slug, ['view_auth_settings', 'update_auth_settings', 'manage_auth_settings'])) {
            return 'Auth Settings';
        }

        // Client/API Management
        if (
            in_array($slug, [
                'view_clients',
                'create_client',
                'update_client',
                'delete_client',
                'manage_clients',
                'generate_api_keys',
                'revoke_api_keys',
                'rotate_api_keys',
                'view_api_key_activity'
            ])
        ) {
            return 'Client Settings';
        }

        // Email Configuration
        if (
            in_array($slug, [
                'view_email_config',
                'update_email_config',
                'manage_email_config',
                'view_email_templates',
                'create_email_template',
                'update_email_template',
                'delete_email_template',
                'manage_email_templates',
                'send_test_email'
            ])
        ) {
            return 'Email Configuration';
        }

        // Storage Management
        if (
            in_array($slug, [
                'view_storage_config',
                'update_storage_config',
                'manage_storage_config',
                'view_storage_analytics',
                'migrate_storage',
                'cleanup_storage',
                'upload_files',
                'bulk_upload_files',
                'delete_files',
                'view_files'
            ])
        ) {
            return 'Storage Management';
        }

        // Logs & Activity
        if (in_array($slug, ['view_logs', 'manage_logs', 'view_activity'])) {
            return 'Logs & Activity';
        }

        // Module Management
        if (in_array($slug, ['view_modules', 'enable_module', 'disable_module', 'manage_modules'])) {
            return 'Module Management';
        }

        // Profile (Customer self-service)
        if (in_array($slug, ['view_own_profile', 'edit_own_profile'])) {
            return 'Profile';
        }

        // AI Integration
        if (
            in_array($slug, [
                'manage_ai_providers',
                'manage_ai_api_keys',
                'view_ai_usage',
                'manage_prompt_templates',
                'call_ai_models',
                'use_prompt_templates',
                'use_ai_chat'
            ])
        ) {
            return 'AI Integration';
        }

        // Notifications
        if (
            in_array($slug, [
                'view_notification_settings',
                'manage_notification_settings',
                'view_notifications',
                'view_admin_notifications',
                'manage_announcements'
            ])
        ) {
            return 'Notification Management';
        }

        // Payment Gateway
        if (
            in_array($slug, [
                'view_payment_gateways',
                'manage_payment_gateways',
                'view_invoice_templates',
                'manage_invoice_templates'
            ])
        ) {
            return 'Payment Gateway';
        }

        // Footage Library
        if (
            in_array($slug, [
                'upload_footage',
                'bulk_upload_footage',
                'view_footage',
                'manage_footage',
                'search_footage',
                'manage_footage_folders',
                'view_all_footage',
                'delete_any_footage',
                'view_footage_stats',
                'view_footage_library',
                'manage_footage_library',
                'use_footage_library'
            ])
        ) {
            return 'FootageLibrary';
        }

        // Audio Library
        if (
            in_array($slug, [
                'upload_audio',
                'bulk_upload_audio',
                'view_audio',
                'manage_audio',
                'manage_audio_folders',
                'view_all_audio',
                'delete_any_audio',
                'view_audio_stats',
                'view_audio_library',
                'manage_audio_library',
                'use_audio_library'
            ])
        ) {
            return 'AudioLibrary';
        }

        // Image Library
        if (
            in_array($slug, [
                'upload_image',
                'bulk_upload_image',
                'view_image',
                'manage_image',
                'manage_image_folders',
                'view_all_image',
                'delete_any_image',
                'view_image_stats',
                'view_image_library',
                'manage_image_library',
                'use_image_library'
            ])
        ) {
            return 'ImageLibrary';
        }

        // BGM Library
        if (
            in_array($slug, [
                'upload_bgm',
                'bulk_upload_bgm',
                'view_bgm',
                'manage_bgm',
                'manage_bgm_folders',
                'view_all_bgm',
                'delete_any_bgm',
                'view_bgm_stats',
                'use_bgm_library'
            ])
        ) {
            return 'BgmLibrary';
        }

        // Video Overlay
        if (
            in_array($slug, [
                'upload_video_overlay',
                'view_video_overlay',
                'manage_video_overlay',
                'manage_video_overlay_folders',
                'view_all_video_overlay',
                'delete_any_video_overlay',
                'view_video_overlay_stats',
                'use_video_overlay'
            ])
        ) {
            return 'VideoOverlay';
        }

        // Image Overlay
        if (
            in_array($slug, [
                'upload_image_overlay',
                'bulk_upload_image_overlay',
                'view_image_overlay',
                'manage_image_overlay',
                'manage_image_overlay_folders',
                'view_all_image_overlay',
                'delete_any_image_overlay',
                'view_image_overlay_stats',
                'use_image_overlay'
            ])
        ) {
            return 'ImageOverlay';
        }

        // Social Connection
        if (in_array($slug, ['manage_social_connection_providers'])) {
            return 'Social Connection';
        }

        // Support Tickets
        if (
            in_array($slug, [
                'view_own_tickets',
                'create_tickets',
                'reply_to_own_tickets',
                'view_all_tickets',
                'manage_tickets',
                'assign_tickets'
            ])
        ) {
            return 'Support';
        }

        return 'Core';
    }
}
