<?php
use PHPUnit\Framework\TestCase;

final class UtilTest extends TestCase
{
    public function testRelativeTime(): void
    {
        $datetime = new DateTimeImmutable();

        $oneMinuteAgo = $datetime->modify('-1 minute')->format('c');
        $relativeTime = Util::relative_time($oneMinuteAgo);
        $this->assertSame($relativeTime, "1 minute ago");

        $twoHoursAgo = $datetime->modify('-2 hours')->format('c');
        $relativeTime = Util::relative_time($twoHoursAgo);
        $this->assertSame($relativeTime, "2 hours ago");

        $threeDaysAgo = $datetime->modify('-3 days')->format('c');
        $relativeTime = Util::relative_time($threeDaysAgo);
        $this->assertSame($relativeTime, "3 days ago");

        $fourMonthsAgo = $datetime->modify('-4 months')->format('c');
        $relativeTime = Util::relative_time($fourMonthsAgo);
        $this->assertSame($relativeTime, "4 months ago");

        $fiveYearsAgo = $datetime->modify('-5 years')->format('c');
        $relativeTime = Util::relative_time($fiveYearsAgo);
        $this->assertSame($relativeTime, "5 years ago");

    }
}