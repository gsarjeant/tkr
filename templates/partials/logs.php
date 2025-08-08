<?php /** @var SettingsModel $settings */ ?>
<?php /** @var array $logEntries */ ?>
<?php /** @var array $availableRoutes */ ?>
<?php /** @var array $availableLevels */ ?>
<?php /** @var string $currentLevelFilter */ ?>
<?php /** @var string $currentRouteFilter */ ?>
        <h1>System Logs</h1>
        <main>
            <!-- Filters -->
            <div class="log-filters">
                <form method="get" action="<?= Util::buildRelativeUrl($settings->basePath, 'admin/logs') ?>">
                    <fieldset>
                        <legend>Filter Logs</legend>
                        <div class="fieldset-items">
                            <label for="level-filter">Level:</label>
                            <select id="level-filter" name="level">
                                <option value="">All Levels</option>
                                <?php foreach ($availableLevels as $level): ?>
                                    <option value="<?= Util::escape_html($level) ?>"
                                            <?= $currentLevelFilter === $level ? 'selected' : '' ?>>
                                        <?= Util::escape_html($level) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="route-filter">Route:</label>
                            <select id="route-filter" name="route">
                                <option value="">All Routes</option>
                                <?php foreach ($availableRoutes as $route): ?>
                                    <option value="<?= Util::escape_html($route) ?>"
                                            <?= $currentRouteFilter === $route ? 'selected' : '' ?>>
                                        <?= Util::escape_html($route) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div></div><button type="submit">Filter</button>
                            <div></div><a href="<?= Util::buildRelativeUrl($settings->basePath, 'admin/logs') ?>">Clear</a>
                        </div>
                    </fieldset>
                </form>
            </div>

            <!-- Log entries table -->
            <div class="log-entries">
                <?php if (empty($logEntries)): ?>
                    <p>No log entries found matching the current filters.</p>
                <?php else: ?>
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Level</th>
                                <th>IP</th>
                                <th>Route</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logEntries as $entry): ?>
                                <tr class="log-entry log-<?= strtolower($entry['level']) ?>">
                                    <td class="log-timestamp log-monospace">
                                        <time datetime="<?= Util::escape_html($entry['timestamp']) ?>">
                                            <?= Util::escape_html($entry['timestamp']) ?>
                                        </time>
                                    </td>
                                    <td class="log-level">
                                        <span class="log-level-badge"><?= Util::escape_html($entry['level']) ?></span>
                                    </td>
                                    <td class="log-ip log-monospace"><?= Util::escape_html($entry['ip']) ?></td>
                                    <td class="log-route log-monospace">
                                        <?php if ($entry['route']): ?>
                                            <?= Util::escape_html($entry['route']) ?>
                                        <?php else: ?>
                                            <span class="log-no-route">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="log-message log-monospace"><?= Util::escape_html($entry['message']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="log-info">
                <p>Showing <?= count($logEntries) ?> recent log entries.
                   Log files are automatically rotated when they reach 1000 lines.</p>
            </div>
        </main>