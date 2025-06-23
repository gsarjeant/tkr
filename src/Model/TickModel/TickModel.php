<?php
class TickModel {
    // Everything in this class just reads from and writes to the filesystem
    // It doesn't maintain state, so everything's just a static function
    public static function streamTicks(int $limit, int $offset = 0): Generator {
        $tick_files = glob(TICKS_DIR . '/*/*/*.txt');
        usort($tick_files, fn($a, $b) => strcmp($b, $a)); // sort filenames in reverse chronological order

        $count = 0;
        foreach ($tick_files as $file) {
            // read all the ticks from the current file and reverse the order
            // so the most recent ones are first
            //
            // each file is a single day, so we never hold more than
            // one day's ticks in memory
            $lines = array_reverse(
                file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            );

            // split the path to the current file into the date components
            $pathParts = explode('/', str_replace('\\', '/', $file));

            // assign the different components to the appropriate part of the date
            $year = $pathParts[count($pathParts) - 3];
            $month = $pathParts[count($pathParts) - 2];
            $day = pathinfo($pathParts[count($pathParts) - 1], PATHINFO_FILENAME);

            foreach ($lines as $line) {
                // just keep skipping ticks until we get to the starting point
                if ($offset > 0) {
                    $offset--;
                    continue;
                }

                // Ticks are pipe-delimited: timestamp|text
                // But just in case a tick contains a pipe, only split on the first one that occurs
                list($time, $mood, $tick) = explode('|', $line, 3);

                // Build the timestamp from the date and time
                // Ticks are always stored in UTC
                $timestampUTC = "$year-$month-$day $time";
                yield [
                    'timestamp' => $timestampUTC,
                    'mood' => $mood,
                    'tick' => $tick,
                ];

                if (++$count >= $limit) {
                    return;
                }
            }
        }
    }

    public static function save(string $tick, string $mood=''): void {
        // build the tick path and filename from the current time
        $now = new DateTime('now', new DateTimeZone('UTC'));

        $year = $now->format('Y');
        $month = $now->format('m');
        $day = $now->format('d');
        $time = $now->format('H:i:s');

        // build the full path to the tick file
        $dir = TICKS_DIR . "/$year/$month";
        $filename = "$dir/$day.txt";

        // create the directory if it doesn't exist
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }

        // write the tick to the file (the file will be created if it doesn't exist)
        $content = $time . '|' . $mood . '|' . $tick . "\n";
        file_put_contents($filename, $content, FILE_APPEND);
    }

    public static function get(string $y, string $m, string $d, string $H, string $i, string $s): array{
        $tickTime = new DateTime("$y-$m-$d $H:$i:$s");
        $timestamp = "$H:$i:$s";
        $file = TICKS_DIR . "/$y/$m/$d.txt";

        if (!file_exists($file)) {
            http_response_code(404);
            echo "Tick not found: $file.";
            exit;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with($line, $timestamp)) {
                echo $line;
                exit;
                list($time, $emoji, $tick) = explode('|', $line, 3);

                return [
                    'tickTime' => $tickTime,
                    'emoji' => $emoji,
                    'tick' => $tick,
                    'config' => ConfigModel::load(),
                ];
            }
        }
    }
}
