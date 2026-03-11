<?php
declare(strict_types=1);

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    /** @var array<int, array{test: string, message: string}> */
    private array $failures = [];

    public function run(string $name, callable $fn): void
    {
        try {
            $fn();
            $this->passed++;
            echo "  PASS  {$name}\n";
        } catch (TestFailure $e) {
            $this->failed++;
            $this->failures[] = ['test' => $name, 'message' => $e->getMessage()];
            echo "  FAIL  {$name}\n";
            echo "        → {$e->getMessage()}\n";
        } catch (\Throwable $e) {
            $this->failed++;
            $this->failures[] = ['test' => $name, 'message' => get_class($e) . ': ' . $e->getMessage()];
            echo "  FAIL  {$name}\n";
            echo "        → " . get_class($e) . ": {$e->getMessage()}\n";
        }
    }

    public function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new TestFailure($message ?: 'Expected true, got false');
        }
    }

    public function assertFalse(bool $condition, string $message = ''): void
    {
        if ($condition) {
            throw new TestFailure($message ?: 'Expected false, got true');
        }
    }

    public function assertEqual(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new TestFailure($message ?: sprintf(
                'Expected %s, got %s',
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    public function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new TestFailure($message ?: "Expected string to contain '{$needle}'");
        }
    }

    public function assertNotContains(string $needle, string $haystack, string $message = ''): void
    {
        if (str_contains($haystack, $needle)) {
            throw new TestFailure($message ?: "Expected string NOT to contain '{$needle}'");
        }
    }

    public function assertNotEmpty(mixed $value, string $message = ''): void
    {
        if (empty($value)) {
            throw new TestFailure($message ?: 'Expected non-empty value');
        }
    }

    public function assertEmpty(mixed $value, string $message = ''): void
    {
        if (!empty($value)) {
            throw new TestFailure($message ?: 'Expected empty value, got ' . var_export($value, true));
        }
    }

    public function assertCount(int $expected, array $actual, string $message = ''): void
    {
        $count = count($actual);
        if ($count !== $expected) {
            throw new TestFailure($message ?: "Expected count {$expected}, got {$count}");
        }
    }

    public function assertGreaterThan(int $expected, int $actual, string $message = ''): void
    {
        if ($actual <= $expected) {
            throw new TestFailure($message ?: "Expected {$actual} > {$expected}");
        }
    }

    public function section(string $name): void
    {
        echo "\n{$name}\n" . str_repeat('-', strlen($name)) . "\n";
    }

    public function summary(): int
    {
        $total = $this->passed + $this->failed;
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Results: {$this->passed} passed, {$this->failed} failed, {$total} total\n";

        if (!empty($this->failures)) {
            echo "\nFailures:\n";
            foreach ($this->failures as $i => $f) {
                echo '  ' . ($i + 1) . ") {$f['test']}\n";
                echo "     {$f['message']}\n";
            }
        }

        echo str_repeat('=', 50) . "\n";
        return $this->failed > 0 ? 1 : 0;
    }
}

class TestFailure extends \RuntimeException {}
