<?php
class TickModel {
    public function __construct(private PDO $db, private ConfigModel $config) {}
    
    public function getPage(int $limit, int $offset = 0): array {
        $stmt = $this->db->prepare("SELECT id, timestamp, tick FROM tick ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        // TODO: Test for existence of row and handle absence.
        return [
            'tickTime' => $row['timestamp'],
            'tick' => $row['tick'],
            'config' => $this->config,
        ];
    }
}
