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
    'use_ai_chat' => 'Use AI chat interface (customer)',

    // Social Connection
    'manage_social_connection_providers' => 'Manage SocialConnection provider apps (OAuth credentials, scopes, enable/disable)',
    'manage_social_connection_groups' => 'Manage connection groups and move connections to groups (customer)',

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
    'view_footage_library' => 'View footage library',
    'manage_footage_library' => 'Manage footage library',
    'use_footage_library' => 'Browse and use shared footage library (read-only)',

    // Audio Library
    'upload_audio' => 'Upload audio',
    'bulk_upload_audio' => 'Bulk upload audio',
    'view_audio' => 'View own audio',
    'manage_audio' => 'Edit/delete own audio',
    'manage_audio_folders' => 'Manage audio folders',
    'view_all_audio' => 'View all audio (admin)',
    'delete_any_audio' => 'Delete any audio (admin)',
    'view_audio_stats' => 'View audio statistics (admin)',
    'view_audio_library' => 'View audio library',
    'manage_audio_library' => 'Manage audio library',
    'use_audio_library' => 'Browse and use shared audio library (read-only)',

    // Image Library
    'upload_image' => 'Upload image',
    'bulk_upload_image' => 'Bulk upload image',
    'view_image' => 'View own image',
    'manage_image' => 'Edit/delete own image',
    'manage_image_folders' => 'Manage image folders',
    'view_all_image' => 'View all image (admin)',
    'delete_any_image' => 'Delete any image (admin)',
    'view_image_stats' => 'View image statistics (admin)',
    'view_image_library' => 'View image library',
    'manage_image_library' => 'Manage image library',
    'use_image_library' => 'Browse and use shared image library (read-only)',

    // BGM Library
    'upload_bgm' => 'Upload BGM',
    'bulk_upload_bgm' => 'Bulk upload BGM',
    'view_bgm' => 'View own BGM',
    'manage_bgm' => 'Edit/delete own BGM',
    'manage_bgm_folders' => 'Manage BGM folders',
    'view_all_bgm' => 'View all BGM (admin)',
    'delete_any_bgm' => 'Delete any BGM (admin)',
    'view_bgm_stats' => 'View BGM statistics (admin)',
    'use_bgm_library' => 'Browse and use shared BGM library (read-only)',

    // Video Overlay
    'upload_video_overlay' => 'Upload video overlay',
    'view_video_overlay' => 'View own video overlay',
    'manage_video_overlay' => 'Edit/delete own video overlay',
    'manage_video_overlay_folders' => 'Manage video overlay folders',
    'view_all_video_overlay' => 'View all video overlay (admin)',
    'delete_any_video_overlay' => 'Delete any video overlay (admin)',
    'view_video_overlay_stats' => 'View video overlay statistics (admin)',
    'use_video_overlay' => 'Browse and use shared video overlays (read-only)',

    // Image Overlay
    'upload_image_overlay' => 'Upload image overlay',
    'bulk_upload_image_overlay' => 'Bulk upload image overlay',
    'view_image_overlay' => 'View own image overlay',
    'manage_image_overlay' => 'Edit/delete own image overlay',
    'manage_image_overlay_folders' => 'Manage image overlay folders',
    'view_all_image_overlay' => 'View all image overlay (admin)',
    'delete_any_image_overlay' => 'Delete any image overlay (admin)',
    'view_image_overlay_stats' => 'View image overlay statistics (admin)',
    'use_image_overlay' => 'Browse and use shared image overlays (read-only)',

    // Media Upload
    'view_media_upload_folders' => 'View media upload folders',
    'manage_media_upload_folders' => 'Manage media upload folders',
    'upload_media' => 'Upload media',
    'manage_media_uploads' => 'Manage media uploads',
    'manage_caption_templates' => 'Manage caption templates',
];
