<?php
    class MoodController extends Controller {
        public function index(){
            global $config;
            global $user;
            $view = new MoodView();

            $moodPicker = $view->render_mood_picker(self::getEmojisWithLabels(), $user->mood);

            $vars = [
                'config' => $config,
                'moodPicker' => $moodPicker,
            ];
            
            $this->render("mood.php", $vars);
        }

        // Shows the custom emoji management page
        public function showCustomEmoji(){
            global $config;
            $emojiList = MoodModel::loadAll();

            $vars = [
                'config' => $config,
                'emojiList' => $emojiList,
            ];

            $this->render("emoji.php", $vars);
        }

        public function handlePost(): void {
            global $config;

            switch ($_POST['action']) {
            case 'add':
                $emoji = trim($_POST['emoji']);
                $description = trim($_POST['emoji-description']);
                $this->handleAdd($emoji, $description);
                break;
            case 'delete':
                if (!empty($_POST['delete_emoji_ids'])){
                    $this->handleDelete();
                }
                break;
            }

            header('Location: ' . $config->basePath . 'admin/emoji');
            exit;
        }

        public function handleAdd(string $emoji, ?string $description=null): void {
            // Validate 1 visible character in the emoji
            if (extension_loaded('mbstring')) {
                // TODO - log a warning if mbstring isn't loaded
                $charCount = mb_strlen($emoji, 'UTF-8');
                if ($charCount !== 1) {
                    // TODO - handle error
                    return;
                }
            }

            // Validate the emoji is actually an emoji
            $emojiPattern = '/^[\x{1F000}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{1F900}-\x{1F9FF}\x{1FA70}-\x{1FAFF}]$/u';
    
            if (!preg_match($emojiPattern, $emoji)) {
                // TODO - handle error
                return;
            }
    
            // emojis should have more bytes than characters
            $byteCount = strlen($emoji);
            if ($byteCount <= 1) {
                // TODO - handle error
                return;
            }
   
            // It looks like an emoji. Let's add it.
            MoodModel::add($emoji, $description);
        }

        public function handleDelete(): void {
            $ids = $_POST['delete_emoji_ids'];

            if (!empty($ids)) {
                $moodModel = new MoodModel();
                $moodModel->delete($ids);
            }
        }

        public function handleSetMood(){
            if ($_SERVER['REQUEST_METHOD'] === 'POST' and isset($_POST['mood'])) {
                // ensure that the session is valid before proceeding
                if (!Session::validateCsrfToken($_POST['csrf_token'])) {
                    die('Invalid CSRF token');
                }

                // Get the data we need
                global $config;
                global $user;
                $mood = $_POST['mood'];

                // set the mood
                $user->mood = $mood;
                $user = $user->save();
            
                // go back to the index and show the updated mood
                header('Location: ' . $config->basePath);
                exit;
            }
        }

        private static function getEmojisWithLabels(): array {
            $customEmoji = MoodModel::loadAll();

            if (!empty($customEmoji)){
                $custom = [];

                foreach ($customEmoji as $item){
                    $custom[] = [$item['emoji'], $item['description']];
                }
            }

            $emoji = [
                'faces' => [
                    ['😀', 'grinning face'],
                    ['😄', 'grinning face with smiling eyes'],
                    ['😁', 'beaming face with smiling eyes'],
                    ['😆', 'grinning squinting face'],
                    ['😅', 'grinning face with sweat'],
                    ['😂', 'face with tears of joy'],
                    ['🤣', 'rolling on the floor laughing'],
                    ['😊', 'smiling face with smiling eyes'],
                    ['😇', 'smiling face with halo'],
                    ['🙂', 'slightly smiling face'],
                    ['🙃', 'upside-down face'],
                    ['😉', 'winking face'],
                    ['😌', 'relieved face'],
                    ['😍', 'smiling face with heart-eyes'],
                    ['🥰', 'smiling face with hearts'],
                    ['😘', 'face blowing a kiss'],
                    ['😗', 'kissing face'],
                    ['😚', 'kissing face with closed eyes'],
                    ['😋', 'face savoring food'],
                    ['😛', 'face with tongue'],
                    ['😜', 'winking face with tongue'],
                    ['😝', 'squinting face with tongue'],
                    ['🤪', 'zany face'],
                    ['🦸', 'superhero'],
                    ['🦹', 'supervillain'],
                    ['🧙', 'mage'],
                    ['🧛', 'vampire'],
                    ['🧟', 'zombie'],
                    ['🧞', 'genie'],
                ],
                'gestures' => [
                    ['👋', 'waving hand'],
                    ['🖖', 'vulcan salute'],
                    ['👌', 'OK hand'],
                    ['🤌', 'pinched fingers'],
                    ['✌️', 'victory hand'],
                    ['🤞', 'crossed fingers'],
                    ['🤟', 'love-you gesture'],
                    ['🤘', 'sign of the horns'],
                    ['🤙', 'call me hand'],
                    ['👍', 'thumbs up'],
                    ['👎', 'thumbs down'],
                    ['✊', 'raised fist'],
                    ['👊', 'oncoming fist'],
                ],
                'nature' => [
                    ['☀️', 'sun'],
                    ['⛅', 'sun behind cloud'],
                    ['🌧️', 'cloud with rain'],
                    ['🌨️', 'cloud with snow'],
                    ['❄️', 'snowflake'],
                    ['🌩️', 'cloud with lightning'],
                    ['🌪️', 'tornado'],
                    ['🌈', 'rainbow'],
                    ['🔥', 'fire'],
                    ['💧', 'droplet'],
                    ['🌊', 'water wave'],
                    ['🌫️', 'fog'],
                    ['🌬️', 'wind face'],
                    ['🍂', 'fallen leaf'],
                    ['🌵', 'cactus'],
                    ['🌴', 'palm tree'],
                    ['🌸', 'cherry blossom'],
                ],
                'animals' => [
                    ['🐶', 'dog face'],
                    ['🐱', 'cat face'],
                    ['🐭', 'mouse face'],
                    ['🐹', 'hamster face'],
                    ['🐰', 'rabbit face'],
                    ['🦊', 'fox face'],
                    ['🐻', 'bear face'],
                    ['🐼', 'panda face'],
                    ['🐨', 'koala'],
                    ['🐯', 'tiger face'],
                    ['🦁', 'lion face'],
                    ['🐮', 'cow face'],
                    ['🐷', 'pig face'],
                    ['🐸', 'frog face'],
                    ['🐵', 'monkey face'],
                    ['🐔', 'chicken'],
                    ['🐧', 'penguin'],
                    ['🐦', 'bird'],
                    ['🐣', 'hatching chick'],
                    ['🐺', 'wolf face'],
                    ['🦄', 'unicorn face'],
                ],
                'hearts' => [
                    ['❤️', 'red heart'],
                    ['🧡', 'orange heart'],
                    ['💛', 'yellow heart'],
                    ['💚', 'green heart'],
                    ['💙', 'blue heart'],
                    ['💜', 'purple heart'],
                    ['🖤', 'black heart'],
                    ['🤍', 'white heart'],
                    ['🤎', 'brown heart'],
                    ['💖', 'sparkling heart'],
                    ['💗', 'growing heart'],
                    ['💓', 'beating heart'],
                    ['💞', 'revolving hearts'],
                    ['💕', 'two hearts'],
                    ['💘', 'heart with arrow'],
                    ['💝', 'heart with ribbon'],
                    ['💔', 'broken heart'],
                    ['❣️', 'heart exclamation'],
                ],
                'activities' => [
                    ['🚴', 'person biking'],
                    ['🚵', 'person mountain biking'],
                    ['🏃', 'person running'],
                    ['🏋️', 'person lifting weights'],
                    ['🏊', 'person swimming'],
                    ['🏄', 'person surfing'],
                    ['🚣', 'person rowing boat'],
                    ['🤸', 'person cartwheeling'],
                    ['🧘', 'person in lotus position'],
                    ['🧗', 'person climbing'],
                    ['🏕️', 'camping'],
                    ['🎣', 'fishing pole'],
                    ['🎿', 'skis'],
                    ['🏂', 'snowboarder'],
                    ['🛹', 'skateboard'],
                    ['🧺', 'basket'],
                    ['🎯', 'bullseye'],
                ],
                'hobbies' => [
                    ['📚', 'books'],
                    ['📖', 'open book'],
                    ['🎧', 'headphone'],
                    ['🎵', 'musical note'],
                    ['🎤', 'microphone'],
                    ['🎷', 'saxophone'],
                    ['🎸', 'guitar'],
                    ['🎹', 'musical keyboard'],
                    ['🎺', 'trumpet'],
                    ['🎻', 'violin'],
                    ['🪕', 'banjo'],
                    ['✍️', 'writing hand'],
                    ['📝', 'memo'],
                    ['📷', 'camera'],
                    ['🎨', 'artist palette'],
                    ['🧵', 'thread'],
                    ['🧶', 'yarn'],
                    ['🪡', 'sewing needle'],
                    ['📹', 'video camera'],
                    ['🎬', 'clapper board'],
                ],
                'food' => [
                    ['🍎', 'red apple'],
                    ['🍌', 'banana'],
                    ['🍇', 'grapes'],
                    ['🍓', 'strawberry'],
                    ['🍉', 'watermelon'],
                    ['🍍', 'pineapple'],
                    ['🥭', 'mango'],
                    ['🍑', 'peach'],
                    ['🍒', 'cherries'],
                    ['🍅', 'tomato'],
                    ['🥦', 'broccoli'],
                    ['🥕', 'carrot'],
                    ['🌽', 'ear of corn'],
                    ['🥔', 'potato'],
                    ['🍞', 'bread'],
                    ['🥐', 'croissant'],
                    ['🥖', 'baguette bread'],
                    ['🧀', 'cheese wedge'],
                    ['🍕', 'pizza'],
                    ['🍔', 'hamburger'],
                    ['🍟', 'french fries'],
                    ['🌭', 'hot dog'],
                    ['🍣', 'sushi'],
                ],
                'vibes' => [
                    ['💤', 'zzz'],
                    ['🤯', 'exploding head'],
                    ['😱', 'face screaming in fear'],
                    ['🥵', 'hot face'],
                    ['🥶', 'cold face'],
                    ['🤬', 'face with symbols on mouth'],
                    ['🤨', 'face with raised eyebrow'],
                ],
                'tech' => [
                    ['💻', 'laptop'],
                    ['📞', 'telephone receiver'],
                    ['🔋', 'battery'],
                    ['💿', 'optical disk'],
                    ['🕹️', 'joystick'],
                    ['🔍', 'magnifying glass tilted left'],
                    ['📈', 'chart increasing'],
                ],
                'travel' => [
                    ['✈️', 'airplane'],
                    ['🚗', 'automobile'],
                    ['🚕', 'taxi'],
                    ['🚲', 'bicycle'],
                    ['🛴', 'kick scooter'],
                    ['⛵', 'sailboat'],
                ],
            ];

            // add custom emoji if there are any
            if (isset($custom)){
                $emoji = ['custom' => $custom] + $emoji;
            }

            return $emoji;
        }
}
?>