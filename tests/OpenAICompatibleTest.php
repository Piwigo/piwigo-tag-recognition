<?php

use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that overrides getFileName() to return a known local path,
 * bypassing the DB calls and DerivativeImage logic in the abstract base class.
 * Defined here (not in production code) so the production class is unchanged.
 */
class TestableOpenAICompatible extends OpenAICompatible
{
    public string $testFilePath = '';

    public function getFileName($imageId): string
    {
        return $this->testFilePath;
    }
}

// ---------------------------------------------------------------------------

class OpenAICompatibleTest extends TestCase
{
    private TestableOpenAICompatible $api;
    private string                   $fixtureFile;
    private string                   $serverBase;

    protected function setUp(): void
    {
        $GLOBALS['pwg_queries'] = [];

        $this->api              = new TestableOpenAICompatible();
        $this->api->testFilePath = __DIR__ . '/fixtures/test_image.jpg';
        $this->fixtureFile      = __DIR__ . '/fixtures/current_response.json';
        $this->serverBase       = 'http://127.0.0.1:' . MOCK_SERVER_PORT;
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fixtureFile)) {
            unlink($this->fixtureFile);
        }
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    /** Skip the test if the mock HTTP server failed to start. */
    private function requireServer(): void
    {
        if (empty($GLOBALS['mock_server_available'])) {
            $this->markTestSkipped('Mock HTTP server could not be started on port ' . MOCK_SERVER_PORT . '.');
        }
    }

    /**
     * Build a minimal valid OpenAI chat-completion response envelope.
     * $content is the raw string placed in choices[0].message.content.
     */
    private function openAIEnvelope(string $content): array
    {
        return [
            'id'      => 'chatcmpl-test',
            'object'  => 'chat.completion',
            'choices' => [
                [
                    'index'         => 0,
                    'message'       => [
                        'role'    => 'assistant',
                        'content' => $content,
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
        ];
    }

    /** Write $data as JSON to the fixture file the mock server will return. */
    private function setMockResponse(array $data): void
    {
        file_put_contents($this->fixtureFile, json_encode($data));
    }

    /**
     * Build a full conf array, overriding individual keys as needed.
     * Defaults point at the local mock server.
     */
    private function conf(array $overrides = []): array
    {
        return array_merge([
            'ENDPOINT'          => $this->serverBase,
            'API_KEY'           => 'test-key',
            'MODEL'             => 'test-model',
            'MAX_TOKENS'        => '100',
            'PROMPT'            => '',
            'WRITE_DESCRIPTION' => '0',
        ], $overrides);
    }

    /** Build a params array, overriding individual keys as needed. */
    private function params(array $overrides = []): array
    {
        return array_merge([
            'imageId'  => 42,
            'language' => 'en',
            'limit'    => 20,
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // Metadata tests — no HTTP call, no curl needed
    // -----------------------------------------------------------------------

    public function testGetInfoReturnsRequiredKeys(): void
    {
        $info = (new OpenAICompatible())->getInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('icon', $info);
        $this->assertArrayHasKey('site', $info);
        $this->assertArrayHasKey('info', $info);

        // site must be a non-empty string (used as href in the template)
        $this->assertIsString($info['site']);
        $this->assertNotEmpty($info['site']);

        // info must be a plain string (not null, not a shell execution result)
        $this->assertIsString($info['info']);
        $this->assertNotEmpty($info['info']);
    }

    public function testGetConfParamsReturnsAllFields(): void
    {
        $params = (new OpenAICompatible())->getConfParams();

        $this->assertIsArray($params);

        $expected = ['ENDPOINT', 'API_KEY', 'MODEL', 'MAX_TOKENS', 'PROMPT', 'WRITE_DESCRIPTION'];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $params, "Missing config param: $key");
            $this->assertIsString($params[$key],    "Label for $key must be a string");
            $this->assertNotEmpty($params[$key],    "Label for $key must not be empty");
        }

        $this->assertCount(6, $params);
    }

    public function testGetConfFieldTypes(): void
    {
        $types = (new OpenAICompatible())->getConfFieldTypes();

        $this->assertIsArray($types);

        $this->assertArrayHasKey('PROMPT',            $types);
        $this->assertArrayHasKey('WRITE_DESCRIPTION', $types);
        $this->assertSame('textarea', $types['PROMPT']);
        $this->assertSame('checkbox', $types['WRITE_DESCRIPTION']);

        // Fields that are plain text inputs must NOT appear in the type map
        $this->assertArrayNotHasKey('ENDPOINT',   $types);
        $this->assertArrayNotHasKey('API_KEY',    $types);
        $this->assertArrayNotHasKey('MODEL',      $types);
        $this->assertArrayNotHasKey('MAX_TOKENS', $types);
    }

    // -----------------------------------------------------------------------
    // Config-validation exceptions — fired before any HTTP call
    // -----------------------------------------------------------------------

    public function testGenerateTagsMissingEndpointThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API parameters are not set');

        $this->api->generateTags($this->conf(['ENDPOINT' => '']), $this->params());
    }

    public function testGenerateTagsMissingModelThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API parameters are not set');

        $this->api->generateTags($this->conf(['MODEL' => '']), $this->params());
    }

    public function testGenerateTagsThrowsWhenImageFileUnreadable(): void
    {
        $this->api->testFilePath = '/nonexistent/path/no_such_image.jpg';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot read image file');

        $this->api->generateTags($this->conf(), $this->params());
    }

    // -----------------------------------------------------------------------
    // Happy-path tag parsing — requires mock server
    // -----------------------------------------------------------------------

    public function testGenerateTagsWithValidJsonResponse(): void
    {
        $this->requireServer();

        $this->setMockResponse($this->openAIEnvelope(json_encode([
            'description' => 'A beach scene at sunset.',
            'tags'        => ['beach', 'ocean', 'sunset'],
        ])));

        $tags = $this->api->generateTags($this->conf(), $this->params());

        $this->assertSame(['beach', 'ocean', 'sunset'], $tags);
    }

    public function testGenerateTagsWithMarkdownFencedJson(): void
    {
        $this->requireServer();

        $fenced = "```json\n" . json_encode([
            'description' => 'A mountain landscape.',
            'tags'        => ['mountain', 'landscape', 'snow', 'sky'],
        ]) . "\n```";

        $this->setMockResponse($this->openAIEnvelope($fenced));

        $tags = $this->api->generateTags($this->conf(), $this->params());

        $this->assertSame(['mountain', 'landscape', 'snow', 'sky'], $tags);
    }

    public function testGenerateTagsWithUnlabelledMarkdownFence(): void
    {
        $this->requireServer();

        // Some models omit the "json" hint after the backticks
        $fenced = "```\n" . json_encode([
            'description' => 'A city skyline.',
            'tags'        => ['city', 'skyline', 'night'],
        ]) . "\n```";

        $this->setMockResponse($this->openAIEnvelope($fenced));

        $tags = $this->api->generateTags($this->conf(), $this->params());

        $this->assertSame(['city', 'skyline', 'night'], $tags);
    }

    public function testGenerateTagsReturnsEmptyArrayWhenTagsKeyMissing(): void
    {
        $this->requireServer();

        // Valid JSON but no "tags" key — should return [] without throwing
        $this->setMockResponse($this->openAIEnvelope(json_encode([
            'description' => 'No tags provided.',
        ])));

        $tags = $this->api->generateTags($this->conf(), $this->params());

        $this->assertSame([], $tags);
    }

    // -----------------------------------------------------------------------
    // limit enforcement
    // -----------------------------------------------------------------------

    public function testGenerateTagsRespectsLimit(): void
    {
        $this->requireServer();

        $this->setMockResponse($this->openAIEnvelope(json_encode([
            'description' => 'Many tags.',
            'tags'        => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'],
        ])));

        $tags = $this->api->generateTags($this->conf(), $this->params(['limit' => 3]));

        $this->assertCount(3, $tags);
        $this->assertSame(['a', 'b', 'c'], $tags);
    }

    // -----------------------------------------------------------------------
    // WRITE_DESCRIPTION feature
    // -----------------------------------------------------------------------

    public function testGenerateTagsWritesDescriptionToDatabase(): void
    {
        $this->requireServer();

        $this->setMockResponse($this->openAIEnvelope(json_encode([
            'description' => 'A serene forest in autumn.',
            'tags'        => ['forest', 'autumn'],
        ])));

        $this->api->generateTags(
            $this->conf(['WRITE_DESCRIPTION' => '1']),
            $this->params(['imageId' => 99])
        );

        $this->assertNotEmpty($GLOBALS['pwg_queries'], 'Expected an UPDATE query to be executed');

        $lastQuery = end($GLOBALS['pwg_queries']);
        $this->assertStringContainsString('UPDATE',                     $lastQuery);
        $this->assertStringContainsString('comment',                    $lastQuery);
        $this->assertStringContainsString('A serene forest in autumn.', $lastQuery);
        $this->assertStringContainsString('99',                         $lastQuery);
    }

    public function testGenerateTagsDescriptionUsesCorrectImageId(): void
    {
        $this->requireServer();

        $this->setMockResponse($this->openAIEnvelope(json_encode([
            'description' => 'Test.',
            'tags'        => ['test'],
        ])));

        $this->api->generateTags(
            $this->conf(['WRITE_DESCRIPTION' => '1']),
            $this->params(['imageId' => 777])
        );

        $updateQuery = end($GLOBALS['pwg_queries']);
        $this->assertStringContainsString('WHERE id = 777', $updateQuery);
    }

    public function testGenerateTagsDoesNotWriteDescriptionWhenDisabled(): void
    {
        $this->requireServer();

        $this->setMockResponse($this->openAIEnvelope(json_encode([
            'description' => 'This should not be saved.',
            'tags'        => ['tag1'],
        ])));

        $this->api->generateTags(
            $this->conf(['WRITE_DESCRIPTION' => '0']),
            $this->params()
        );

        $updateQueries = array_filter(
            $GLOBALS['pwg_queries'],
            static fn(string $q): bool => str_contains(strtoupper($q), 'UPDATE')
        );
        $this->assertCount(0, $updateQueries, 'No UPDATE should be issued when WRITE_DESCRIPTION is disabled');
    }

    public function testGenerateTagsDoesNotWriteDescriptionWhenDescriptionEmpty(): void
    {
        $this->requireServer();

        // JSON with no "description" key → description stays '' → no UPDATE
        $this->setMockResponse($this->openAIEnvelope(json_encode([
            'tags' => ['only', 'tags'],
        ])));

        $this->api->generateTags(
            $this->conf(['WRITE_DESCRIPTION' => '1']),
            $this->params()
        );

        $updateQueries = array_filter(
            $GLOBALS['pwg_queries'],
            static fn(string $q): bool => str_contains(strtoupper($q), 'UPDATE')
        );
        $this->assertCount(0, $updateQueries, 'No UPDATE should fire when description is empty');
    }

    // -----------------------------------------------------------------------
    // Free-text fallback (model ignores JSON instructions)
    // -----------------------------------------------------------------------

    public function testGenerateTagsWithFreeTextFallback(): void
    {
        $this->requireServer();

        // Model returns plain comma-separated keywords instead of JSON
        $this->setMockResponse($this->openAIEnvelope('cat, dog, pet, animal'));

        $tags = $this->api->generateTags($this->conf(), $this->params());

        $this->assertNotEmpty($tags);
        $this->assertContains('cat',    $tags);
        $this->assertContains('dog',    $tags);
        $this->assertContains('pet',    $tags);
        $this->assertContains('animal', $tags);
    }

    public function testFreeTextFallbackFiltersLongSentences(): void
    {
        $this->requireServer();

        $this->setMockResponse($this->openAIEnvelope(
            "sunset\nThis is a very long sentence that should be filtered out\nocean"
        ));

        $tags = $this->api->generateTags($this->conf(), $this->params());

        $this->assertContains('sunset', $tags);
        $this->assertContains('ocean',  $tags);
        $this->assertNotContains(
            'This is a very long sentence that should be filtered out',
            $tags
        );
    }

    public function testFreeTextFallbackFiltersEntryLongerThan50Chars(): void
    {
        $this->requireServer();

        $long = str_repeat('x', 51); // 51 chars, single word
        $this->setMockResponse($this->openAIEnvelope("valid_tag\n{$long}\nanother_tag"));

        $tags = $this->api->generateTags($this->conf(), $this->params());

        $this->assertContains('valid_tag',   $tags);
        $this->assertContains('another_tag', $tags);
        $this->assertNotContains($long, $tags);
    }

    public function testFreeTextFallbackRespectsLimit(): void
    {
        $this->requireServer();

        $this->setMockResponse($this->openAIEnvelope("alpha\nbeta\ngamma\ndelta\nepsilon"));

        $tags = $this->api->generateTags($this->conf(), $this->params(['limit' => 2]));

        $this->assertCount(2, $tags);
    }

    public function testFreeTextFallbackWritesRawContentAsDescription(): void
    {
        $this->requireServer();

        $rawText = 'cat, dog, pet';
        $this->setMockResponse($this->openAIEnvelope($rawText));

        $this->api->generateTags(
            $this->conf(['WRITE_DESCRIPTION' => '1']),
            $this->params(['imageId' => 7])
        );

        $lastQuery = end($GLOBALS['pwg_queries']);
        $this->assertNotFalse($lastQuery, 'Expected an UPDATE query for free-text description');
        $this->assertStringContainsString('cat, dog, pet', $lastQuery);
    }

    // -----------------------------------------------------------------------
    // Error-handling
    // -----------------------------------------------------------------------

    public function testGenerateTagsThrowsOnMalformedApiResponse(): void
    {
        $this->requireServer();

        // Server returns a valid HTTP 200 but an error body (no choices key)
        $this->setMockResponse([
            'error' => [
                'message' => 'model not found',
                'type'    => 'invalid_request_error',
            ],
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('API Error');

        $this->api->generateTags($this->conf(), $this->params());
    }

    public function testGenerateTagsThrowsOnConnectionError(): void
    {
        // Port 17891 has nothing listening → immediate ECONNREFUSED
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection error');

        $this->api->generateTags(
            $this->conf(['ENDPOINT' => 'http://127.0.0.1:17891']),
            $this->params()
        );
    }
}
