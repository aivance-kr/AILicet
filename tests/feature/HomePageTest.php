<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class HomePageTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testRootShowsLandingWithLoginButton(): void
    {
        $result = $this->get('/');

        $result->assertStatus(200);
        $result->assertSee('Permission, verified.');
        $this->assertStringContainsString('href="/admin/login"', (string) $result->getBody());
    }

    public function testSiteUrlOmitsIndexPhp(): void
    {
        $this->assertStringNotContainsString('index.php', site_url('admin/login'));
    }
}
