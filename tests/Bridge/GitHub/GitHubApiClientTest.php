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
use PackApi\Exception\ApiException;
use PackApi\Exception\NetworkException;
use PackApi\Exception\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(GitHubApiClient::class)]
final class GitHubApiClientTest extends TestCase
{
    private function getMockClient(array $responses, array &$calls): MockHttpClient
    {
        return new MockHttpClient(function (string $method, string $url, array $options = []) use (&$calls, $responses) {
            $path = (string) parse_url($url, PHP_URL_PATH);
            $calls[] = [$method, $path, $options];
            $key = $method.' '.$path;
            [$status, $data] = $responses[$key] ?? [200, []];

            return new MockResponse(json_encode($data), ['http_code' => $status]);
        });
    }

    public function testExtractRepoNameReturnsNullForEmptyString(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([], $calls));

        $this->assertNull($client->extractRepoName(''));
    }

    public function testRepositoryMethodsRejectInvalidName(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([], $calls));
        $operations = [
            static fn (): mixed => $client->fetchRepoMetadata('invalid'),
            static fn (): mixed => $client->fetchRepoActivity('invalid'),
            static fn (): mixed => $client->fetchSecurityAdvisories('invalid'),
            static fn (): mixed => $client->fetchRepoFiles('invalid'),
            static fn (): mixed => $client->fetchRepoContents('invalid'),
        ];

        foreach ($operations as $operation) {
            try {
                $operation();
                $this->fail('A malformed repository name must be rejected.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame([], $calls);
    }

    public function testFetchRepoMetadataMakesCorrectRequest(): void
    {
        $responses = [
            'GET /repos/owner/repo' => [200, ['name' => 'repo']],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $data = $client->fetchRepoMetadata('owner/repo');

        $this->assertCount(1, $calls);
        $this->assertSame('GET', $calls[0][0]);
        $this->assertSame('/repos/owner/repo', $calls[0][1]);
        $this->assertSame(['name' => 'repo'], $data);
    }

    public function testFetchRepoMetadataReturnsNullWhenRepositoryDoesNotExist(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/missing' => [404, ['message' => 'Not Found']],
        ], $calls));

        $this->assertNull($client->fetchRepoMetadata('owner/missing'));
    }

    public function testFetchRepoMetadataRethrowsApiErrors(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/repo' => [500, ['message' => 'Unavailable']],
        ], $calls));

        try {
            $client->fetchRepoMetadata('owner/repo');
            $this->fail('The API error must be rethrown.');
        } catch (ApiException $exception) {
            $this->assertSame(500, $exception->httpCode);
            $this->assertSame('GitHub API error: Unavailable', $exception->getMessage());
        }
    }

    public function testFetchRepoActivityAggregatesRepositoryActivity(): void
    {
        $responses = [
            'GET /repos/owner/repo' => [200, ['name' => 'repo']],
            'GET /repos/owner/repo/commits' => [200, [
                ['commit' => ['committer' => ['date' => '2026-08-20T12:00:00Z']]],
                ['commit' => ['committer' => ['date' => '2026-08-19T12:00:00Z']]],
            ]],
            'GET /repos/owner/repo/contributors' => [200, [['login' => 'one'], ['login' => 'two']]],
            'GET /repos/owner/repo/releases' => [200, [['published_at' => '2026-08-18T12:00:00Z']]],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $activity = $client->fetchRepoActivity('owner/repo');

        $this->assertNotNull($activity);
        $this->assertSame(['name' => 'repo'], $activity['repository']);
        $this->assertSame(2, $activity['activity_stats']['commit_count_last_year']);
        $this->assertSame(2, $activity['activity_stats']['contributor_count']);
        $this->assertSame(1, $activity['activity_stats']['release_count']);
        $this->assertSame('2026-08-20T12:00:00Z', $activity['activity_stats']['last_commit_date']);
        $this->assertSame('2026-08-18T12:00:00Z', $activity['activity_stats']['last_release_date']);
        $this->assertSame(100, $calls[1][2]['query']['per_page']);
        $this->assertArrayHasKey('since', $calls[1][2]['query']);
        $this->assertSame(50, $calls[2][2]['query']['per_page']);
        $this->assertSame(10, $calls[3][2]['query']['per_page']);
    }

    public function testFetchRepoActivityReturnsNullWhenRepositoryDoesNotExist(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/missing' => [404, ['message' => 'Not Found']],
        ], $calls));

        $this->assertNull($client->fetchRepoActivity('owner/missing'));
    }

    public function testFetchRepoActivityReturnsNullWhenActivityEndpointIsMissing(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/repo' => [200, ['name' => 'repo']],
            'GET /repos/owner/repo/commits' => [404, ['message' => 'Not Found']],
        ], $calls));

        $this->assertNull($client->fetchRepoActivity('owner/repo'));
    }

    public function testFetchRepoActivityRethrowsUnexpectedErrors(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/repo' => [200, ['name' => 'repo']],
            'GET /repos/owner/repo/commits' => [500, ['message' => 'Unavailable']],
        ], $calls));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('GitHub API error: Unavailable');
        $client->fetchRepoActivity('owner/repo');
    }

    public function testSearchRepositoriesUsesQueryParameters(): void
    {
        $responses = [
            'GET /search/repositories' => [200, ['items' => []]],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $client->searchRepositories('symfony', 5, 'stars', 'desc');

        $this->assertCount(1, $calls);
        [$method, $url, $options] = $calls[0];
        $this->assertSame('GET', $method);
        $this->assertSame('/search/repositories', $url);
        $this->assertSame(
            ['q' => 'symfony', 'per_page' => 5, 'sort' => 'stars', 'order' => 'desc'],
            $options['query'] ?? []
        );
    }

    public function testSearchRepositoriesRethrowsApiErrors(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /search/repositories' => [422, ['message' => 'Invalid query']],
        ], $calls));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('GitHub API error: Invalid query');
        $client->searchRepositories('broken');
    }

    public function testFetchSecurityAdvisoriesAggregatesData(): void
    {
        $responses = [
            'GET /repos/owner/repo/security-advisories' => [200, [['id' => 1]]],
            'GET /repos/owner/repo/vulnerability-alerts' => [403, []],
            'GET /repos/owner/repo/contents/SECURITY.md' => [404, []],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $data = $client->fetchSecurityAdvisories('owner/repo');

        $this->assertCount(3, $calls);
        $this->assertSame(1, $data['advisory_count']);
        $this->assertFalse($data['has_security_policy']);
    }

    public function testFetchSecurityAdvisoriesIncludesAlertsAndSecurityPolicy(): void
    {
        $responses = [
            'GET /repos/owner/repo/security-advisories' => [200, [['id' => 1], ['id' => 2]]],
            'GET /repos/owner/repo/vulnerability-alerts' => [200, ['enabled' => true]],
            'GET /repos/owner/repo/contents/SECURITY.md' => [200, ['name' => 'SECURITY.md']],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $data = $client->fetchSecurityAdvisories('owner/repo');

        $this->assertNotNull($data);
        $this->assertSame(['enabled' => true], $data['vulnerability_alerts']);
        $this->assertSame(2, $data['advisory_count']);
        $this->assertTrue($data['has_security_policy']);
    }

    public function testFetchSecurityAdvisoriesReturnsNullWhenEndpointIsMissing(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/repo/security-advisories' => [404, ['message' => 'Not Found']],
        ], $calls));

        $this->assertNull($client->fetchSecurityAdvisories('owner/repo'));
    }

    public function testFetchSecurityAdvisoriesRethrowsUnexpectedAlertsError(): void
    {
        $responses = [
            'GET /repos/owner/repo/security-advisories' => [200, []],
            'GET /repos/owner/repo/vulnerability-alerts' => [500, ['message' => 'Unavailable']],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('GitHub API error: Unavailable');
        $client->fetchSecurityAdvisories('owner/repo');
    }

    public function testFetchSecurityAdvisoriesRethrowsSecurityPolicyErrors(): void
    {
        $responses = [
            'GET /repos/owner/repo/security-advisories' => [200, []],
            'GET /repos/owner/repo/vulnerability-alerts' => [200, []],
            'GET /repos/owner/repo/contents/SECURITY.md' => [500, ['message' => 'Unavailable']],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('GitHub API error: Unavailable');
        $client->fetchSecurityAdvisories('owner/repo');
    }

    public function testFetchRepoFilesBuildsRepositoryOverview(): void
    {
        $responses = [
            'GET /repos/owner/repo' => [200, ['name' => 'repo']],
            'GET /repos/owner/repo/contents' => [200, [['name' => 'README.md'], ['name' => 'src']]],
            'GET /repos/owner/repo/contents/README.md' => [200, ['name' => 'README.md']],
            'GET /repos/owner/repo/contents/LICENSE' => [200, ['name' => 'LICENSE']],
            'GET /repos/owner/repo/contents/SECURITY.md' => [200, ['name' => 'SECURITY.md']],
            'GET /repos/owner/repo/contents/composer.json' => [404, ['message' => 'Not Found']],
            'GET /repos/owner/repo/contents/package.json' => [404, ['message' => 'Not Found']],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $files = $client->fetchRepoFiles('owner/repo');

        $this->assertNotNull($files);
        $this->assertSame('main', $files['default_branch']);
        $this->assertSame(2, $files['file_count']);
        $this->assertTrue($files['has_readme']);
        $this->assertTrue($files['has_license']);
        $this->assertTrue($files['has_security_policy']);
        $this->assertArrayNotHasKey('composer.json', $files['important_files']);
        $this->assertSame(['ref' => 'main'], $calls[1][2]['query']);
    }

    public function testFetchRepoFilesReturnsNullWhenRepositoryDoesNotExist(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/missing' => [404, ['message' => 'Not Found']],
        ], $calls));

        $this->assertNull($client->fetchRepoFiles('owner/missing'));
    }

    public function testFetchRepoFilesReturnsNullWhenContentsEndpointIsMissing(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/repo' => [200, ['name' => 'repo']],
            'GET /repos/owner/repo/contents' => [404, ['message' => 'Not Found']],
        ], $calls));

        $this->assertNull($client->fetchRepoFiles('owner/repo'));
    }

    public function testFetchRepoFilesRethrowsUnexpectedFileError(): void
    {
        $responses = [
            'GET /repos/owner/repo' => [200, ['default_branch' => 'stable']],
            'GET /repos/owner/repo/contents' => [200, []],
            'GET /repos/owner/repo/contents/README.md' => [500, ['message' => 'Unavailable']],
        ];
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient($responses, $calls));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('GitHub API error: Unavailable');
        $client->fetchRepoFiles('owner/repo');
    }

    public function testFetchRepoContentsRethrowsApiErrors(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/repo/contents/file.txt' => [500, ['message' => 'Unavailable']],
        ], $calls));

        $this->expectException(ApiException::class);
        $client->fetchRepoContents('owner/repo', 'file.txt');
    }

    public function testFetchFileContentReturnsNullWithoutContent(): void
    {
        $calls = [];
        $client = new GitHubApiClient($this->getMockClient([
            'GET /repos/owner/repo/contents/file.txt' => [200, ['name' => 'file.txt']],
        ], $calls));

        $this->assertNull($client->fetchFileContent('owner/repo', 'file.txt'));
    }

    public function testInvalidJsonResponseThrowsApiException(): void
    {
        $client = new GitHubApiClient(new MockHttpClient([
            new MockResponse('{invalid'),
        ]));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid JSON response from GitHub API');
        $client->fetchRepoMetadata('owner/repo');
    }

    public function testTransportErrorsBecomeNetworkExceptions(): void
    {
        $transport = new class('Connection failed') extends \RuntimeException implements TransportExceptionInterface {};
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException($transport);
        $client = new GitHubApiClient($httpClient);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Network error while calling GitHub API: Connection failed');
        $client->fetchRepoMetadata('owner/repo');
    }

    public function testHttpClientErrorsBecomeApiExceptions(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $httpError = new class($response) extends \RuntimeException implements ClientExceptionInterface {
            public function __construct(private readonly ResponseInterface $response)
            {
                parent::__construct('Request failed');
            }

            public function getResponse(): ResponseInterface
            {
                return $this->response;
            }
        };
        $httpResponse = $this->createStub(ResponseInterface::class);
        $httpResponse->method('getStatusCode')->willReturn(200);
        $httpResponse->method('getContent')->willThrowException($httpError);
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($httpResponse);
        $client = new GitHubApiClient($httpClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('HTTP error while calling GitHub API: Request failed');
        $client->fetchRepoMetadata('owner/repo');
    }
}
