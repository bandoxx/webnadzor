<?php

namespace App\Tests\Service\Exception;

use App\Service\Exception\ExceptionFormatter;
use PHPUnit\Framework\TestCase;

class ExceptionFormatterTest extends TestCase
{
    public function testStringFormatsExceptionCorrectly(): void
    {
        $exception = new \Exception('Test error message', 42);

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString('Error: Test error message', $result);
        $this->assertStringContainsString('Code: 42', $result);
        $this->assertStringContainsString('File:', $result);
        $this->assertStringContainsString('Line:', $result);
    }

    public function testStringFormatsRuntimeException(): void
    {
        $exception = new \RuntimeException('Runtime error');

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString('Error: Runtime error', $result);
        $this->assertStringContainsString(__FILE__, $result);
    }

    public function testStringFormatsExceptionWithEmptyMessage(): void
    {
        $exception = new \Exception('');

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString('Error: ', $result);
        $this->assertStringContainsString('Code: 0', $result);
    }

    public function testStringFormatsExceptionWithSpecialCharacters(): void
    {
        $exception = new \Exception('Error: <script>alert("xss")</script>');

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString('<script>alert("xss")</script>', $result);
    }

    public function testStringFormatsNestedExceptionCorrectly(): void
    {
        $inner = new \Exception('Inner error', 100);
        $outer = new \Exception('Outer error', 200, $inner);

        $result = ExceptionFormatter::string($outer);

        $this->assertStringContainsString('Error: Outer error', $result);
        $this->assertStringContainsString('Code: 200', $result);
    }

    public function testStringIncludesLineNumber(): void
    {
        try {
            throw new \Exception('Test'); // Line number captured here
        } catch (\Exception $e) {
            $result = ExceptionFormatter::string($e);
            $this->assertMatchesRegularExpression('/Line: \d+/', $result);
        }
    }

    public function testStringFormatsInvalidArgumentException(): void
    {
        $exception = new \InvalidArgumentException('Invalid argument provided', 1);

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString('Error: Invalid argument provided', $result);
        $this->assertStringContainsString('Code: 1', $result);
    }

    public function testStringFormatOutputStructure(): void
    {
        $exception = new \Exception('Message', 123);

        $result = ExceptionFormatter::string($exception);

        // Verify the format contains all expected parts in order
        $this->assertMatchesRegularExpression(
            '/^Error: .+\nLine: \d+\nCode: \d+\nFile: .+\n$/',
            $result
        );
    }

    public function testStringFormatsErrorWithZeroCode(): void
    {
        $exception = new \Exception('Zero code error', 0);

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString('Code: 0', $result);
    }

    public function testStringFormatsExceptionWithNegativeCode(): void
    {
        $exception = new \Exception('Negative code', -1);

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString('Code: -1', $result);
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testStringWithVariousExceptions(string $class, string $message, int $code): void
    {
        $exception = new $class($message, $code);

        $result = ExceptionFormatter::string($exception);

        $this->assertStringContainsString("Error: $message", $result);
        $this->assertStringContainsString("Code: $code", $result);
    }

    public static function exceptionDataProvider(): array
    {
        return [
            'basic exception' => [\Exception::class, 'Basic error', 1],
            'runtime exception' => [\RuntimeException::class, 'Runtime error', 2],
            'invalid argument' => [\InvalidArgumentException::class, 'Invalid arg', 3],
            'logic exception' => [\LogicException::class, 'Logic error', 4],
            'out of range' => [\OutOfRangeException::class, 'Out of range', 5],
        ];
    }
}
