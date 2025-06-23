<?php /** @var ConfigModel $config */ ?>
<?php /** @var array $emojiList */ ?>
        <h1>Emoji Management</h1>
        <main>
            <form action="<?= $config->basePath ?>admin/emoji" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                <fieldset>
                    <legend>Add Emoji</legend>
                    <div class="fieldset-items">
                        <label for="emoji">Enter an emoji</label>
                        <input type="text" id="emoji" name="emoji"
                               required maxlength="4" minlength="1"
                               pattern="^[\u{1F000}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F1E0}-\u{1F1FF}\u{1F900}-\u{1F9FF}\u{1FA70}-\u{1FAFF}]$"
                               placeholder="Enter an emoji"
                        >
                        <label for="emoji-description">Description</label>
                        <input type="text" id="emoji-description" name="emoji-description"
                               maxlength="40" minlength="1"
                               placeholder="describe the mood"
                        >
                        <div></div>
                        <button type="submit" name="action" value="add">Add emoji</button>
                    </div>
                </fieldset>
            </form>
<?php if (!empty($emojiList)): ?>
            <form action="<?= $config->basePath ?>admin/emoji" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                <fieldset class="delete-emoji-fieldset">
                    <legend>Delete Emoji</legend>
                    <div class="fieldset-items">
                        <?php foreach ($emojiList as $emojiItem): ?>
                            <div class="delete-emoji-item">
                                <input type="checkbox"
                                       id="delete_emoji_<?= Util::escape_html($emojiItem['id']) ?>"
                                       name="delete_emoji_ids[]"
                                       value="<?= Util::escape_html($emojiItem['id']) ?>">
                                <label for="delete_emoji_<?= Util::escape_html($emojiItem['id']) ?>">
                                    <span class="delete-emoji-display"><?= Util::escape_html($emojiItem['emoji']) ?></span>
                                    <span class="emoji-description"><?= Util::escape_html($emojiItem['description']) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" name="action" value="delete">Delete selected emoji</button>
                    </div>
                </fieldset>
<?php endif; ?>
            </form>
        </main>
