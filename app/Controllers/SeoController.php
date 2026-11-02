<?php

namespace App\Controllers;

use App\Libraries\Seo\LandingContent;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 검색·AI 크롤러용 공개 텍스트 — robots.txt / sitemap.xml / llms.txt.
 * 공개 정보만 담는다. 절대경로는 base_url() 기준.
 */
class SeoController extends BaseController
{
    /** AI 검색·학습 크롤러 — 소개 페이지 노출을 위해 허용 */
    private const AI_CRAWLERS = ['GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended'];

    /** GET /robots.txt */
    public function robots(): ResponseInterface
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /agency',
            'Disallow: /client',
            'Disallow: /api',
            '',
        ];
        foreach (self::AI_CRAWLERS as $bot) {
            $lines[] = 'User-agent: ' . $bot;
            $lines[] = 'Allow: /';
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /agency';
            $lines[] = 'Disallow: /client';
            $lines[] = 'Disallow: /api';
            $lines[] = '';
        }
        $lines[] = 'Sitemap: ' . base_url('sitemap.xml');

        return $this->text(implode("\n", $lines) . "\n", 'text/plain');
    }

    /** GET /sitemap.xml */
    public function sitemap(): ResponseInterface
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . '  <url><loc>' . esc(base_url('/')) . '</loc><changefreq>monthly</changefreq><priority>1.0</priority></url>' . "\n"
            . '</urlset>' . "\n";

        return $this->text($xml, 'application/xml');
    }

    /** GET /llms.txt — llmstxt.org 형식 */
    public function llms(): ResponseInterface
    {
        $lines = [
            '# AILicet',
            '',
            '> ' . LandingContent::SUMMARY,
            '',
            '## 주요 페이지',
            '',
            '- [서비스 소개](' . base_url('/') . '): ' . LandingContent::DESCRIPTION,
            '',
            '## 주요 기능',
            '',
        ];
        foreach (LandingContent::features() as $f) {
            $lines[] = '- ' . $f['title'] . ': ' . $f['body'];
        }
        $lines[] = '';
        $lines[] = '## 자주 묻는 질문';
        foreach (LandingContent::faqs() as $f) {
            $lines[] = '';
            $lines[] = '### ' . $f['q'];
            $lines[] = $f['a'];
        }

        return $this->text(implode("\n", $lines) . "\n", 'text/plain');
    }

    private function text(string $body, string $type): ResponseInterface
    {
        return $this->response->setContentType($type, 'utf-8')->setBody($body);
    }
}
