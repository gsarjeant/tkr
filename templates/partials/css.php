<?php /** @var ConfigModel $config */ ?>
<?php /** @var Array $customCss */ ?>
        <h1>CSS Management</h1>
        <main>
            <form action="<?= $config->basePath ?>admin/css" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                <fieldset>
                    <legend>Manage</legend>
                    <div class="fieldset-items">
                        <label for="selectCssFile">Select CSS File</label>
                        <select id="selectCssFile" name="selectCssFile" value=<?= $config->cssId ?>>
                            <option value="">Default</option>
<?php foreach ($customCss as $cssFile): ?>
    <?php
        if ($cssFile['id'] == $config->cssId){
            $cssDescription = $cssFile['description'];
            $selected = "selected";
        }
    ?>

                            <option value=<?= $cssFile['id'] ?>
                                    <?= isset($selected) ? $selected : ""?>>
                                    <?=Util::escape_html($cssFile['filename'])?>
                            </option>
<?php endforeach; ?>
                        </select>
<?php if (isset($cssDescription) && $cssDescription): ?>
                        <label>Description</label>
                        <label class="css-description"><?= Util::escape_html($cssDescription) ?></label>
<?php endif; ?>
                        <div></div>
                        <div>
                            <button type="submit" name="action" value="set_theme">Set Theme</button>
                            <button type="submit" name="action" value="delete" class="delete-btn">Delete</button>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend>Upload</legend>
                    <div class="fieldset-items">
                        <input type="hidden" name="csrf_token" value="<?= Util::escape_html($_SESSION['csrf_token']) ?>">
                        <label for="uploadCssFile">Select File to Upload</label>
                        <input type="file"
                               id="uploadCssFile"
                               name="uploadCssFile"
                               accept=".css">
                        <div class="file-info">
                            <strong>File Requirements:</strong><br>
                            • Must be a valid CSS file (.css extension)<br>
                            • Maximum size: 1 MB<br>
                            • Will be scanned for malicious content
                        </div>
                        <label for="description">Description (optional)</label>
                        <textarea id="description"
                                  name="description"
                                  placeholder="Describe this CSS file..."></textarea>
                        <div></div>
                        <button type="submit" name="action" value="upload">Upload CSS File</button>
                    </div>
                </fieldset>
            </form>
</main>
