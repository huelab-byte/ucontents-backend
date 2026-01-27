<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System-Wide Permissions
    |--------------------------------------------------------------------------
    |
    | This file defines all system-wide permissions that can be assigned
    | to roles and checked via policies.
    |
    | Format: 'permission_name' => 'Description of permission'
    |
    */

    // User Management
    'view_users' => 'View list of users',
    'create_user' => 'Create new users',
    'update_user' => 'Update existing users',
    'delete_user' => 'Delete users',
    'manage_users' => 'Full access to user management',

    // Role & Permission Management
    'view_roles' => 'View list of roles',
    'create_role' => 'Create new roles',
    'update_role' => 'Update existing roles',
    'delete_role' => 'Delete roles',
    'manage_roles' => 'Full access to role management',
    'view_permissions' => 'View list of permissions',
    'create_permission' => 'Create new permissions',
    'update_permission' => 'Update existing permissions',
    'delete_permission' => 'Delete permissions',
    'manage_permissions' => 'Full access to permission management',

    // Module Management
    'view_modules' => 'View list of modules',
    'enable_module' => 'Enable modules',
    'disable_module' => 'Disable modules',
    'manage_modules' => 'Full access to module management',

    // Dashboard & Analytics
    'view_dashboard' => 'View dashboard',
    'view_analytics' => 'View system analytics and reports',

    // General Settings
    'view_general_settings' => 'View general settings',
    'update_general_settings' => 'Update general settings',
    'manage_general_settings' => 'Full access to general settings management',

    // Authentication Settings
    'view_auth_settings' => 'View authentication settings',
    'update_auth_settings' => 'Update authentication settings',
    'manage_auth_settings' => 'Full access to authentication settings',

    // Client/API Management
    'view_clients' => 'View list of API clients',
    'create_client' => 'Create new API clients',
    'update_client' => 'Update existing API clients',
    'delete_client' => 'Delete API clients',
    'manage_clients' => 'Full access to client management',
    'generate_api_keys' => 'Generate API keys for clients',
    'revoke_api_keys' => 'Revoke API keys',
    'rotate_api_keys' => 'Rotate API keys',
    'view_api_key_activity' => 'View API key activity logs',

    // Email Configuration
    'view_email_config' => 'View email/SMTP configuration',
    'update_email_config' => 'Update email/SMTP configuration',
    'manage_email_config' => 'Full access to email configuration',
    'view_email_templates' => 'View email templates',
    'create_email_template' => 'Create email templates',
    'update_email_template' => 'Update email templates',
    'delete_email_template' => 'Delete email templates',
    'manage_email_templates' => 'Full access to email template management',
    'send_test_email' => 'Send test emails',

    // Storage Management
    'view_storage_config' => 'View storage configuration',
    'update_storage_config' => 'Update storage configuration',
    'manage_storage_config' => 'Full access to storage configuration',
    'view_storage_analytics' => 'View storage usage analytics',
    'migrate_storage' => 'Migrate storage between drivers',
    'cleanup_storage' => 'Clean up unused storage files',
    'upload_files' => 'Upload files',
    'bulk_upload_files' => 'Bulk upload files',
    'delete_files' => 'Delete files',
    'view_files' => 'View files',

    // Logs & Activity
    'view_logs' => 'View system logs',
    'manage_logs' => 'Manage and clear logs',
    'view_activity' => 'View activity logs',

    // Profile (Customer self-service)
    'view_own_profile' => 'View own profile',
    'edit_own_profile' => 'Edit own profile',

    // AI Integration
    'manage_ai_providers' => 'Full access to AI provider management',
    'manage_ai_api_keys' => 'Full access to AI API key management',
    'view_ai_usage' => 'View AI usage statistics and logs',
    'manage_prompt_templates' => 'Full access to prompt template management',
    'call_ai_models' => 'Call AI models (customer)',
    'use_prompt_templates' => 'Use prompt templates (customer)',

    // Social Connection
    'manage_social_connection_providers' => 'Manage SocialConnection provider apps (OAuth credentials, scopes, enable/disable)',

    // Notifications
    'view_notification_settings' => 'View notification settings (Pusher, realtime)',
    'manage_notification_settings' => 'Manage notification settings (Pusher, realtime)',
    'view_notifications' => 'View own notifications (customer)',
    'view_admin_notifications' => 'View admin notifications (admin)',
    'manage_announcements' => 'Create/manage announcements (admin)',

    // Support Tickets
    'view_own_tickets' => 'View own support tickets',
    'create_tickets' => 'Create support tickets',
    'reply_to_own_tickets' => 'Reply to own tickets',
    'view_all_tickets' => 'View all support tickets',
    'manage_tickets' => 'Manage support tickets',
    'assign_tickets' => 'Assign support tickets',

    // Payment Gateway
    'view_payment_gateways' => 'View payment gateways',
    'manage_payment_gateways' => 'Manage payment gateways',
    'view_invoice_templates' => 'View invoice templates',
    'manage_invoice_templates' => 'Manage invoice templates',

    // Footage Library
    'upload_footage' => 'Upload footage',
    'bulk_upload_footage' => 'Bulk upload footage',
    'view_footage' => 'View own footage',
    'manage_footage' => 'Edit/delete own footage',
    'search_footage' => 'Use AI search for footage',
    'manage_footage_folders' => 'Manage footage folders',
    'view_all_footage' => 'View all footage (admin)',
    'delete_any_footage' => 'Delete any footage (admin)',
    'view_footage_stats' => 'View footage statistics (admin)',
];
