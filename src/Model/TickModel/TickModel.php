<?php
class TickModel {
    public function stream(int $limit, int $offset = 0): Generator {
        global $db;

        $stmt = $db->prepare("SELECT id, timestamp, tick FROM tick ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield [
                'id' => $row['id'],
                'timestamp' => $row['timestamp'],
                'tick' => $row['tick'],
            ];
        }
    }

    public function insert(string $tick, ?DateTimeImmutable $datetime = null): void {
        global $db;
        $datetime ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $timestamp = $datetime->format('Y-m-d H:i:s');

        $stmt = $db->prepare("INSERT INTO tick(timestamp, tick) values (?, ?)");
        $stmt->execute([$timestamp, $tick]);
    }

    public function get(int $id): array {
        global $db;

        $stmt = $db->prepare("SELECT timestamp, tick FROM tick WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // TODO: Test for existence of row and handle absence.
        return [
            'tickTime' => $row['timestamp'],
            'tick' => $row['tick'],
            'config' => ConfigModel::load(),
        ];
    }
}
