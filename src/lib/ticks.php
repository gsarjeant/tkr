<?php

function save_tick(string $tick): void {
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
    $content = $time . "|" . $tick . "\n";
    file_put_contents($filename, $content, FILE_APPEND);
}

// TODO - move this into a view along with
//        the code that builds the tick list.
function stream_ticks(int $limit, int $offset = 0): Generator {
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
            $tickParts = explode('|', $line, 2);
            $time = $tickParts[0];
            $tick = $tickParts[1];

            // Build the timestamp from the date and time
            // Ticks are always stored in UTC
            $timestampUTC = "$year-$month-$day $time";
            yield [
                'timestamp' => $timestampUTC,
                'tick' => $tick,
            ];

            if (++$count >= $limit) {
                return;
            }
        }
    }
}
