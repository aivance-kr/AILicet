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

    public function testLandingHasSeoMetaJsonLdAndFaq(): void
    {
        $body = (string) $this->get('/')->getBody();

        $this->assertSame(1, substr_count($body, '<h1'));
        $this->assertStringContainsString('<link rel="canonical" href="', $body);
        $this->assertStringContainsString('property="og:title"', $body);
        $this->assertStringContainsString('name="twitter:card"', $body);
        $this->assertStringContainsString('"@type":"SoftwareApplication"', $body);
        $this->assertStringContainsString('"@type":"FAQPage"', $body);
        $this->assertStringContainsString('자주 묻는 질문', $body);
    }

    public function testRobotsAllowsAiCrawlersAndListsSitemap(): void
    {
        $body = (string) $this->get('robots.txt')->getBody();

        foreach (['GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'OAI-SearchBot'] as $bot) {
            $this->assertStringContainsString('User-agent: ' . $bot, $body);
        }
        $this->assertStringContainsString('Sitemap: ', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
    }

    public function testSitemapAndLlmsServePublicContent(): void
    {
        $this->assertStringContainsString('<urlset', (string) $this->get('sitemap.xml')->getBody());

        $llms = (string) $this->get('llms.txt')->getBody();
        $this->assertStringStartsWith('# AILicet', $llms);
        $this->assertStringContainsString('노드락 라이선스', $llms);
    }
}
