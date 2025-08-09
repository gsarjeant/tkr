<?php
declare(strict_types=1);

class TickModel {
    public function __construct(private PDO $db, private SettingsModel $settings) {}

    public function getPage(int $limit, int $offset = 0): array {
        $stmt = $this->db->prepare("SELECT id, timestamp, tick FROM tick ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        
        $ticks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($tick) {
            $tickTime = new DateTimeImmutable($tick['timestamp'], new DateTimeZone('UTC'));
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $hoursSinceCreation = ($now->getTimestamp() - $tickTime->getTimestamp()) / 3600;
            
            $tick['can_delete'] = $hoursSinceCreation <= $this->settings->tickDeleteHours;
            return $tick;
        }, $ticks);
    }

    public function insert(string $tick, ?DateTimeImmutable $datetime = null): void {
        $datetime ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $timestamp = $datetime->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare("INSERT INTO tick(timestamp, tick) values (?, ?)");
        $stmt->execute([$timestamp, $tick]);
    }

    public function get(int $id): array {
        $stmt = $this->db->prepare("SELECT timestamp, tick FROM tick WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Handle case where tick doesn't exist
        if ($row === false || empty($row) || !isset($row['timestamp']) || !isset($row['tick'])) {
            return [];
        }

        return [
            'tickTime' => $row['timestamp'],
            'tick' => $row['tick'],
            'settings' => $this->settings,
        ];
    }

    public function delete(int $id): bool {
        // Get tick and validate
        $stmt = $this->db->prepare("SELECT tick, timestamp FROM tick WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row === false || empty($row)) {
            Session::setFlashMessage('error', 'Tick not found');
            return false;
        }
        
        // Check deletion window
        $tickTime = new DateTimeImmutable($row['timestamp'], new DateTimeZone('UTC'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $hoursSinceCreation = ($now->getTimestamp() - $tickTime->getTimestamp()) / 3600;
        
        if ($hoursSinceCreation > $this->settings->tickDeleteHours) {
            Session::setFlashMessage('error', 'Tick is too old to delete');
            return false;
        }
        
        // Delete and set success message
        $stmt = $this->db->prepare("DELETE FROM tick WHERE id=?");
        $stmt->execute([$id]);
        
        Session::setFlashMessage('success', "Deleted: '{$row['tick']}'");
        return true;
    }
}
