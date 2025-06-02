<?php
class HomeController{
    private function stream_ticks(int $limit, int $offset = 0): Generator {
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

    public function render(){
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $isLoggedIn = isset($_SESSION['user_id']);
        $config = Config::load();
        $user = User::load();

        $limit = $config->itemsPerPage;
        $offset = ($page - 1) * $limit;
        $ticks = iterator_to_array(stream_ticks($limit, $offset));

        $view = new HomeView();
        $tickList = $view->renderTicksSection($config->siteDescription, $ticks, $page, $limit);

        $vars = [
            'isLoggedIn' => $isLoggedIn,
            'config'     => $config,
            'user'       => $user,
            'tickList'      => $tickList,
        ];

        echo render_template(TEMPLATES_DIR . "/home.php", $vars);

    }
}