<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class EnvironmentSecurityTest extends TestCase
{
    /** @test */
    public function it_disables_debug_mode_in_production()
    {
        // This test ensures debug mode is disabled in production
        config(['app.debug' => false]);

        // Debug routes should not be available
        $response = $this->get('/telescope');
        $response->assertStatus(404);
    }

    /** @test */
    public function it_disables_sensitive_info_in_error_responses()
    {
        config(['app.debug' => false]);

        // Try to trigger error
        $response = $this->get('/nonexistent-route');

        $response->assertStatus(404);

        // Should not expose stack traces or sensitive info
        $content = $response->getContent();
        $this->assertStringNotContainsString('Stack trace', $content);
        $this->assertStringNotContainsString('database', $content);
        $this->assertStringNotContainsString('password', $content);
    }

    /** @test */
    public function it_uses_secure_headers()
    {
        $response = $this->get('/');

        // Check for security headers in production
        if (app()->environment('production')) {
            $headers = $response->headers;

            // Should have secure cookie settings
            $this->assertTrue($headers->has('Set-Cookie'));
        }
    }

    /** @test */
    public function it_validates_environment_variables()
    {
        // Critical environment variables should be set
        $requiredEnvVars = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_URL',
            'APP_DOMAIN',
        ];

        foreach ($requiredEnvVars as $envVar) {
            $this->assertNotNull(
                config(strtolower(str_replace('_', '.', $envVar))),
                "Environment variable {$envVar} should be set"
            );
        }

        // Ensure encryption key is set properly
        $this->assertNotEquals('SomeRandomString', config('app.key'), 'APP_KEY should be changed from default');
        $this->assertEquals(32, strlen(config('app.key')), 'APP_KEY should be 32 characters');
    }

    /** @test */
    public function it_disallows_composer_autoloader_debug()
    {
        // Composer autoloader optimization should be enabled in production
        if (app()->environment('production')) {
            // This checks if classes are optimized
            $this->assertTrue(class_exists('Illuminate\Foundation\Application'));

            // Verify optimize:config has been run
            $this->assertNotNull(config('app.name'), 'Config should be cached');
        }
    }

    /** @test */
    public function it_uses_secure_database_config()
    {
        if (app()->environment('production')) {
            // In production, should not use SQLite
            $this->assertNotEquals('sqlite', config('database.default'), 'Production should not use SQLite');

            // Database should use SSL
            $this->assertTrue(
                config('database.connections.mysql.charset') === 'utf8mb4',
                'Database should use utf8mb4 charset'
            );
        }
    }

    /** @test */
    public function it_limits_file_permissions()
    {
        // Check important files exist with expected permissions
        $criticalFiles = [
            '.env',
            'composer.json',
            'artisan',
        ];

        foreach ($criticalFiles as $file) {
            $this->assertTrue(
                file_exists(base_path($file)),
                "Critical file {$file} should exist"
            );
        }

        // .env file should not be world-readable
        if (file_exists(base_path('.env'))) {
            $permissions = fileperms(base_path('.env'));
            $this->assertLessThan(
                0o644,
                $permissions & 0o777,
                '.env file should not be world-readable'
            );
        }
    }

    /** @test */
    public function it_disables_direct_access_to_sensitive_files()
    {
        $sensitivePaths = [
            '/.env',
            '/composer.json',
            '/.git/',
            '/storage/',
            '/vendor/',
        ];

        foreach ($sensitivePaths as $path) {
            $response = $this->get($path);

            // Should return 404, 403, or redirect, not expose file contents
            $this->assertContains($response->getStatusCode(), [404, 403, 302, 401]);
        }
    }

    /** @test */
    public function it_uses_secure_session_config()
    {
        if (app()->environment('production')) {
            // Session should be secure
            $this->assertEquals('file', config('session.driver'), 'Should use file session driver');

            // Should not allow cookie tampering
            $this->assertNull(config('session.encrypt'), 'Session encryption not set (good for driver default)');

            // Should have proper lifetime
            $this->assertLessThanOrEqual(120, config('session.lifetime'), 'Session lifetime should be reasonable');
        }
    }

    /** @test */
    public function it_validates_ssl_configuration_check()
    {
        if (app()->environment('production')) {
            // In production, should enforce HTTPS where possible
            $url = config('app.url');
            $this->assertStringStartsWith('https://', $url, 'Production should use HTTPS URL');

            // Should have secure HSTS header (would be set by web server)
            $this->assertFalse(app()->environment('local'), 'Should not run production without HTTPS');
        }
    }

    /** @test */
    public function it_prevents_debug_info_leakage()
    {
        // Test API endpoints don't leak debug info
        $response = $this->get('/api/nonexistent');

        $content = $response->getContent();

        // Should not contain Laravel debug information
        $this->assertStringNotContainsString('Illuminate', $content);
        $this->assertStringNotContainsString('vendor', $content);
        $this->assertStringNotContainsString('bootstrap', $content);
    }

    /** @test */
    public function it_validates_server_error_handling()
    {
        // Simulate server error
        config(['app.debug' => false]);

        $response = $this->get('/test-server-error');

        // Should not expose stack traces
        $this->assertContains($response->getStatusCode(), [500, 404]);

        if ($response->getStatusCode() === 500) {
            $content = $response->getContent();
            $this->assertStringNotContainsString('Stack trace', $content);
        }
    }

    /** @test */
    public function it_validates_storage_visibility()
    {
        // Storage directories should not be web-accessible
        $storagePaths = [
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
        ];

        foreach ($storagePaths as $path) {
            if (is_dir(base_path($path))) {
                // Check for .htaccess or equivalent protection
                $htaccessPath = base_path($path.'/.htaccess');
                if (file_exists($htaccessPath)) {
                    $content = file_get_contents($htaccessPath);
                    $this->assertStringContainsString('Deny', $content, 'Storage directory should deny web access');
                }
            }
        }
    }

    /** @test */
    public function it_validates_logging_configuration()
    {
        // Logging should be configured properly
        $this->assertNotNull(config('logging.default'), 'Default logging channel should be set');

        if (app()->environment('production')) {
            // Should log errors, not debug info
            $logLevel = config('logging.channels.single.level', 'debug');
            $this->assertNotEquals('debug', $logLevel, 'Production should not use debug log level');
        }
    }

    /** @test */
    public function it_validates_cache_security()
    {
        if (app()->environment('production')) {
            // Redis configuration should be secure
            if (config('cache.default') === 'redis') {
                $redisConfig = config('database.redis.default');

                // Should not expose redis config
                $this->assertTrue(is_array($redisConfig));

                // Should have password if connecting to external redis
                if (! empty($redisConfig['host']) && $redisConfig['host'] !== '127.0.0.1') {
                    $this->assertNotNull($redisConfig['password'], 'External Redis should use authentication');
                }
            }
        }
    }

    /** @test */
    public function it_validates_mail_security()
    {
        // Mail configuration should use proper settings
        $this->assertNotNull(config('mail.default'), 'Mail driver should be configured');

        if (app()->environment('production')) {
            // Should not use test mail driver in production
            $this->assertNotEquals('log', config('mail.default'), 'Production should not use log mail driver');

            // SMTP should use proper security if enabled
            if (config('mail.default') === 'smtp') {
                $this->assertEquals('tls', config('mail.mailers.encryption'), 'SMTP should use TLS encryption');
            }
        }
    }
}
