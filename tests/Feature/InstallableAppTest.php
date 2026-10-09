<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallableAppTest extends TestCase
{
    public function test_the_pwa_does_not_cache_private_or_financial_pages(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
        $worker = (string) file_get_contents(public_path('sw.js'));
        $capacitor = json_decode((string) file_get_contents(base_path('capacitor.config.json')), true);

        $this->assertSame('Ma Résidence', $manifest['name']);
        $this->assertSame('Résidence', $manifest['short_name']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
        $this->assertFileExists(public_path('icons/icon-180.png'));

        $this->assertStringContainsString('skipWaiting', $worker);
        $this->assertStringContainsString("request.mode === 'navigate'", $worker);
        $this->assertStringContainsString('text/html', $worker);
        $this->assertStringContainsString('espace|moi|admin|fichiers|connexion|inscription|deconnexion', $worker);
        $this->assertStringNotContainsString('indexedDB', $worker);
        $this->assertStringNotContainsString('localStorage', $worker);
        $this->assertStringContainsString("key !== CACHE", $worker);

        $this->assertSame('Ma Résidence', $capacitor['appName']);
        $this->assertSame('cd.edosservices.maresidence', $capacitor['appId']);
        $this->assertSame('https', $capacitor['server']['androidScheme']);
        $this->assertFalse($capacitor['server']['cleartext']);
        $this->assertArrayNotHasKey('url', $capacitor['server']);
    }
}
