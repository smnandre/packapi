<?php

declare(strict_types=1);

/*
 * This file is part of the smnandre/packapi package.
 *
 * (c) Simon Andre <smn.andre@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PackApi\Tests\Bridge\GitHub;

use PackApi\Bridge\GitHub\GitHubApiClient;
use PackApi\Bridge\GitHub\GitHubSearchProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[CoversClass(GitHubSearchProvider::class)]
final class GitHubSearchProviderTest extends TestCase
{
    public function testSearchMapsResults(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'items' => [
                    [
                        'full_name' => 'owner/repo',
                        'name' => 'repo',
                        'description' => 'desc',
                        'html_url' => 'https://github.com/owner/repo',
                    ],
                ],
            ])),
        ];
        $api = new GitHubApiClient(new MockHttpClient($responses));

        $provider = new GitHubSearchProvider($api);

        $result = $provider->search('term', 2);

        $this->assertSame([
            [
                'identifier' => 'owner/repo',
                'name' => 'repo',
                'description' => 'desc',
                'repository' => 'https://github.com/owner/repo',
            ],
        ], $result);
    }

    public function testSearchByKeywordDelegatesToSearchAndMapsResults(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'items' => [
                    [
                        'full_name' => 'foo/bar',
                        'name' => 'bar',
                        'description' => 'library',
                        'html_url' => 'https://github.com/foo/bar',
                    ],
                ],
            ])),
        ];

        $api = new GitHubApiClient(new MockHttpClient($responses));
        $provider = new GitHubSearchProvider($api);

        $result = $provider->searchByKeyword('php');

        $this->assertSame([
            [
                'identifier' => 'foo/bar',
                'name' => 'bar',
                'description' => 'library',
                'repository' => 'https://github.com/foo/bar',
            ],
        ], $result);
    }

    public function testGetPopularMapsRepositoriesSortedByStars(): void
    {
        $responses = [
            new MockResponse(json_encode([
                'items' => [
                    [
                        'full_name' => 'popular/project',
                        'name' => 'project',
                        'description' => 'Popular project',
                        'html_url' => 'https://github.com/popular/project',
                    ],
                ],
            ])),
        ];
        $api = new GitHubApiClient(new MockHttpClient($responses));
        $provider = new GitHubSearchProvider($api);

        $result = $provider->getPopular(1);

        $this->assertSame([
            [
                'identifier' => 'popular/project',
                'name' => 'project',
                'description' => 'Popular project',
                'repository' => 'https://github.com/popular/project',
            ],
        ], $result);
    }
}
