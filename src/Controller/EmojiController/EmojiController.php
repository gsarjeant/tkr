<?php
    class EmojiController extends Controller {
        // Shows the custom emoji management page
        public function index(){
            global $config;
            $emojiList = EmojiModel::loadAll();

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

            header('Location: ' . Util::buildRelativeUrl($config->basePath, 'admin/emoji'));
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
            EmojiModel::add($emoji, $description);
        }

        public function handleDelete(): void {
            $ids = $_POST['delete_emoji_ids'];

            if (!empty($ids)) {
                EmojiModel::delete($ids);
            }
        }
    }