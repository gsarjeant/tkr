<?php
class HomeController{
    // GET handler
    // renders the homepage view.
    public function index(){
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $isLoggedIn = isset($_SESSION['user_id']);
        $config = Config::load();
        $user = User::load();

        $limit = $config->itemsPerPage;
        $offset = ($page - 1) * $limit;
        $ticks = iterator_to_array($this->stream_ticks($limit, $offset));

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

    // POST handler
    // Saves the tick and reloads the homepage
    public function handleTick(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['tick'])) {
            // ensure that the session is valid before proceeding
            if (!validateCsrfToken($_POST['csrf_token'])) {
                // TODO: maybe redirect to login? Maybe with tick preserved?
                die('Invalid CSRF token');
            }

            // save the tick
            $this->save_tick($_POST['tick']);
        }

        // get the config
        $config = Config::load();

        // redirect to the index (will show the latest tick if one was sent)
        header('Location: ' . $config->basePath);
        exit;
    }

    // TODO - move to a Tick model
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

    // TODO - move to a Tick model
    private function save_tick(string $tick): void {
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
}