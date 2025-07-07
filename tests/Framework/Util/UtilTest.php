<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class UtilTest extends TestCase
{
    // Define test date (strings) and expected outputs for
    // testCanDisplayRelativeTime
    public static function dateProvider(): array {
        $datetime = new DateTimeImmutable();

        return [
            '1 minute ago' => [$datetime->modify('-1 minute')->format('c'), '1 minute ago'],
            '2 hours ago'  => [$datetime->modify('-2 hours')->format('c'), '2 hours ago'],
            '3 days ago'   => [$datetime->modify('-3 days')->format('c'), '3 days ago'],
            '4 months ago' => [$datetime->modify('-4 months')->format('c'), '4 months ago'],
            '5 years ago'  => [$datetime->modify('-5 years')->format('c'), '5 years ago']
        ];
    }

    // Validate that the datetime strings provided by dateProvider
    // yield the expected display strings
    #[DataProvider('dateProvider')]
    public function testCanDisplayRelativeTime(string $datetimeString, string $display): void {
        $relativeTime = Util::relative_time($datetimeString);
        $this->assertSame($relativeTime, $display);
    }

}