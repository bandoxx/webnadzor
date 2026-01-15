<?php

namespace App\Tests\Service\Security;

use App\Service\Security\RecaptchaValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RecaptchaValidatorTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
    }

    public function testValidateReturnsTrueWhenNoSecretConfigured(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, null);

        $this->httpClient->expects($this->never())->method('request');

        $result = $validator->validate('any-token');

        $this->assertTrue($result);
    }

    public function testValidateReturnsTrueWhenEmptySecretConfigured(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, '');

        $this->httpClient->expects($this->never())->method('request');

        $result = $validator->validate('any-token');

        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseWhenTokenIsNull(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $this->httpClient->expects($this->never())->method('request');

        $result = $validator->validate(null);

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseWhenTokenIsEmpty(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $this->httpClient->expects($this->never())->method('request');

        $result = $validator->validate('');

        $this->assertFalse($result);
    }

    public function testValidateReturnsTrueOnSuccessfulVerification(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $response = $this->createMockResponse(200, ['success' => true]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => 'secret-key',
                    'response' => 'valid-token',
                    'remoteip' => '192.168.1.1',
                ],
            ])
            ->willReturn($response);

        $result = $validator->validate('valid-token', '192.168.1.1');

        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseOnFailedVerification(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $response = $this->createMockResponse(200, ['success' => false]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $validator->validate('invalid-token');

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseOnNon200StatusCode(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $response = $this->createMockResponse(500, []);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $validator->validate('token');

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseOnHttpClientException(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'));

        $result = $validator->validate('token');

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseWhenSuccessKeyMissing(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $response = $this->createMockResponse(200, ['error-codes' => ['invalid-input-response']]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $validator->validate('token');

        $this->assertFalse($result);
    }

    public function testValidateWithoutRemoteIp(): void
    {
        $validator = new RecaptchaValidator($this->httpClient, 'secret-key');

        $response = $this->createMockResponse(200, ['success' => true]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => 'secret-key',
                    'response' => 'token',
                    'remoteip' => null,
                ],
            ])
            ->willReturn($response);

        $result = $validator->validate('token', null);

        $this->assertTrue($result);
    }

    private function createMockResponse(int $statusCode, array $data): ResponseInterface&MockObject
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('toArray')->willReturn($data);

        return $response;
    }
}
