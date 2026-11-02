<?php

declare(strict_types=1);

namespace App\Libraries\Seo;

/**
 * 소개 페이지 schema.org JSON-LD 그래프 빌더 (GEO).
 * `<script type="application/ld+json">` 한 덩어리(@graph)로 렌더한다.
 *
 * - JSON_HEX_TAG : `</script>` 조기 종료 방지
 * - JSON_UNESCAPED_UNICODE 미사용 : 한글을 \uXXXX 로 출력
 */
final class JsonLdBuilder
{
    private const ORG_ID  = '#organization';
    private const SITE_ID = '#website';
    private const APP_ID  = '#software';

    /**
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        return [
            '@type' => 'Organization',
            '@id'   => base_url(self::ORG_ID),
            'name'  => 'AIvance',
            'url'   => base_url('/'),
            'logo'  => base_url('apple-touch-icon.png'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function website(): array
    {
        return [
            '@type'       => 'WebSite',
            '@id'         => base_url(self::SITE_ID),
            'name'        => 'AILicet',
            'url'         => base_url('/'),
            'inLanguage'  => 'ko-KR',
            'description' => LandingContent::DESCRIPTION,
            'publisher'   => ['@id' => base_url(self::ORG_ID)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function softwareApplication(): array
    {
        return [
            '@type'               => 'SoftwareApplication',
            '@id'                 => base_url(self::APP_ID),
            'name'                => 'AILicet',
            'url'                 => base_url('/'),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem'     => 'Web',
            'description'         => LandingContent::SUMMARY,
            'inLanguage'          => 'ko-KR',
            'featureList'         => array_column(LandingContent::features(), 'title'),
            'publisher'           => ['@id' => base_url(self::ORG_ID)],
        ];
    }

    /**
     * @param list<array{q: string, a: string}> $faqs
     *
     * @return array<string, mixed>
     */
    public function faqPage(array $faqs): array
    {
        return [
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(static fn (array $f): array => [
                '@type'          => 'Question',
                'name'           => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $faqs),
        ];
    }

    /**
     * 소개 페이지 전체 그래프를 script 태그용 JSON 문자열로 만든다.
     */
    public function landingJson(): string
    {
        $graph = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                $this->organization(),
                $this->website(),
                $this->softwareApplication(),
                $this->faqPage(LandingContent::faqs()),
            ],
        ];

        return (string) json_encode($graph, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);
    }
}
