<?php

declare(strict_types=1);

namespace Modules\EmailManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\EmailManagement\Models\EmailTemplate;

/**
 * Seeder for default system email templates
 */
class DefaultEmailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome-email',
                'subject' => 'Welcome to {{app_name}}!',
                'body_html' => $this->getWelcomeEmailHtml(),
                'body_text' => $this->getWelcomeEmailText(),
                'variables' => ['name', 'app_name', 'login_url'],
                'category' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Password Reset',
                'slug' => 'password-reset',
                'subject' => 'Reset Your Password',
                'body_html' => $this->getPasswordResetHtml(),
                'body_text' => $this->getPasswordResetText(),
                'variables' => ['name', 'reset_url', 'expires_in', 'app_name'],
                'category' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Magic Link',
                'slug' => 'magic-link',
                'subject' => 'Your Login Link',
                'body_html' => $this->getMagicLinkHtml(),
                'body_text' => $this->getMagicLinkText(),
                'variables' => ['name', 'magic_link', 'expires_in', 'app_name'],
                'category' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'OTP Code',
                'slug' => 'otp-code',
                'subject' => 'Your Verification Code',
                'body_html' => $this->getOtpCodeHtml(),
                'body_text' => $this->getOtpCodeText(),
                'variables' => ['name', 'otp_code', 'expires_in', 'app_name'],
                'category' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Email Verification',
                'slug' => 'email-verification',
                'subject' => 'Verify Your Email Address',
                'body_html' => $this->getEmailVerificationHtml(),
                'body_text' => $this->getEmailVerificationText(),
                'variables' => ['name', 'verification_url', 'app_name'],
                'category' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Password Changed',
                'slug' => 'password-changed',
                'subject' => 'Your Password Has Been Changed',
                'body_html' => $this->getPasswordChangedHtml(),
                'body_text' => $this->getPasswordChangedText(),
                'variables' => ['name', 'changed_at', 'app_name', 'support_url'],
                'category' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Account Activated',
                'slug' => 'account-activated',
                'subject' => 'Your Account Has Been Activated',
                'body_html' => $this->getAccountActivatedHtml(),
                'body_text' => $this->getAccountActivatedText(),
                'variables' => ['name', 'login_url', 'app_name'],
                'category' => 'notification',
                'is_active' => true,
            ],
            [
                'name' => 'Set Password',
                'slug' => 'set-password',
                'subject' => 'Welcome to {{app_name}} - Set Your Password',
                'body_html' => $this->getSetPasswordHtml(),
                'body_text' => $this->getSetPasswordText(),
                'variables' => ['name', 'set_password_url', 'expires_in', 'app_name'],
                'category' => 'notification',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $templateData) {
            EmailTemplate::firstOrCreate(
                ['slug' => $templateData['slug']],
                $templateData
            );
        }

        $this->command->info('Default email templates seeded successfully.');
    }

    private function getWelcomeEmailHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0;">Welcome to {{app_name}}!</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>We're excited to have you on board! Your account has been successfully created.</p>
        <p>You can now access all the features and start using our platform.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{login_url}}" style="background: #667eea; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Get Started</a>
        </div>
        <p>If you have any questions, feel free to reach out to our support team.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getWelcomeEmailText(): string
    {
        return <<<'TEXT'
Welcome to {{app_name}}!

Hi {{name}},

We're excited to have you on board! Your account has been successfully created.

You can now access all the features and start using our platform.

Get started: {{login_url}}

If you have any questions, feel free to reach out to our support team.

Best regards,
The {{app_name}} Team
TEXT;
    }

    private function getPasswordResetHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0;">Password Reset Request</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>We received a request to reset your password for your {{app_name}} account.</p>
        <p>Click the button below to reset your password. This link will expire in {{expires_in}} minutes.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{reset_url}}" style="background: #f5576c; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a>
        </div>
        <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getPasswordResetText(): string
    {
        return <<<'TEXT'
Password Reset Request

Hi {{name}},

We received a request to reset your password for your {{app_name}} account.

Click the link below to reset your password. This link will expire in {{expires_in}} minutes.

Reset Password: {{reset_url}}

If you didn't request a password reset, please ignore this email or contact support if you have concerns.

Best regards,
The {{app_name}} Team
TEXT;
    }

    private function getMagicLinkHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0;">Your Login Link</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>Click the button below to securely log in to your {{app_name}} account. This link will expire in {{expires_in}} minutes.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{magic_link}}" style="background: #4facfe; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Login to {{app_name}}</a>
        </div>
        <p>If you didn't request this login link, please ignore this email or contact support if you have concerns.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getMagicLinkText(): string
    {
        return <<<'TEXT'
Your Login Link

Hi {{name}},

Click the link below to securely log in to your {{app_name}} account. This link will expire in {{expires_in}} minutes.

Login: {{magic_link}}

If you didn't request this login link, please ignore this email or contact support if you have concerns.

Best regards,
The {{app_name}} Team
TEXT;
    }

    private function getOtpCodeHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0;">Your Verification Code</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>Your verification code for {{app_name}} is:</p>
        <div style="text-align: center; margin: 30px 0;">
            <div style="background: #fff; border: 2px solid #fa709a; border-radius: 10px; padding: 20px; display: inline-block;">
                <h2 style="color: #fa709a; font-size: 36px; letter-spacing: 5px; margin: 0;">{{otp_code}}</h2>
            </div>
        </div>
        <p>This code will expire in {{expires_in}} minutes.</p>
        <p>If you didn't request this code, please ignore this email or contact support if you have concerns.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getOtpCodeText(): string
    {
        return <<<'TEXT'
Your Verification Code

Hi {{name}},

Your verification code for {{app_name}} is:

{{otp_code}}

This code will expire in {{expires_in}} minutes.

If you didn't request this code, please ignore this email or contact support if you have concerns.

Best regards,
The {{app_name}} Team
TEXT;
    }

    private function getEmailVerificationHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0;">Verify Your Email</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>Thank you for signing up for {{app_name}}! Please verify your email address by clicking the button below.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{verification_url}}" style="background: #30cfd0; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Verify Email Address</a>
        </div>
        <p>If you didn't create an account, please ignore this email.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getEmailVerificationText(): string
    {
        return <<<'TEXT'
Verify Your Email

Hi {{name}},

Thank you for signing up for {{app_name}}! Please verify your email address by clicking the link below.

Verify Email: {{verification_url}}

If you didn't create an account, please ignore this email.

Best regards,
The {{app_name}} Team
TEXT;
    }

    private function getPasswordChangedHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #333; margin: 0;">Password Changed</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>This is a confirmation that your password for your {{app_name}} account was changed on {{changed_at}}.</p>
        <p>If you made this change, you can safely ignore this email.</p>
        <p>If you didn't make this change, please contact our support team immediately at {{support_url}}.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getPasswordChangedText(): string
    {
        return <<<'TEXT'
Password Changed

Hi {{name}},

This is a confirmation that your password for your {{app_name}} account was changed on {{changed_at}}.

If you made this change, you can safely ignore this email.

If you didn't make this change, please contact our support team immediately at {{support_url}}.

Best regards,
The {{app_name}} Team
TEXT;
    }

    private function getAccountActivatedHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0;">Account Activated</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>Great news! Your {{app_name}} account has been activated and is ready to use.</p>
        <p>You can now log in and start using all the features available to you.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{login_url}}" style="background: #84fab0; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Login Now</a>
        </div>
        <p>If you have any questions, feel free to reach out to our support team.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getAccountActivatedText(): string
    {
        return <<<'TEXT'
Account Activated

Hi {{name}},

Great news! Your {{app_name}} account has been activated and is ready to use.

You can now log in and start using all the features available to you.

Login: {{login_url}}

If you have any questions, feel free to reach out to our support team.

Best regards,
The {{app_name}} Team
TEXT;
    }

    private function getSetPasswordHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: #fff; margin: 0;">Welcome to {{app_name}}!</h1>
    </div>
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <p>Hi {{name}},</p>
        <p>An account has been created for you on {{app_name}}.</p>
        <p>Please click the button below to set your password and activate your account. This link will expire in {{expires_in}} minutes.</p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{set_password_url}}" style="background: #667eea; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Set Password</a>
        </div>
        <p>If you did not expect this email, please ignore it or contact support.</p>
        <p>Best regards,<br>The {{app_name}} Team</p>
    </div>
</body>
</html>
HTML;
    }

    private function getSetPasswordText(): string
    {
        return <<<'TEXT'
Welcome to {{app_name}}!

Hi {{name}},

An account has been created for you on {{app_name}}.

Please click the link below to set your password and activate your account. This link will expire in {{expires_in}} minutes.

Set Password: {{set_password_url}}

If you did not expect this email, please ignore it or contact support.

Best regards,
The {{app_name}} Team
TEXT;
    }
}
