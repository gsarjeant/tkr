<?php
// welp this model is overkill
class MoodModel{
    // This isn't memory-efficient,
    // but I think it'll be fine on this app's scales.
    public static function loadAll(): array {
        global $db;

        $stmt = $db->query("SELECT id, emoji, description FROM mood");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // I'm not going to support editing emoji.
    // It'll just be a delete/readd
    public static function add(string $emoji, ?string $description): void{
        global $db;

        $stmt = $db->prepare("INSERT INTO mood (emoji, description) VALUES (?, ?)");
        $stmt->execute([$emoji, $description]);
    }

    public static function delete(array $idsToDelete): void{
        global $db;

        $placeholders = rtrim(str_repeat('?,', count($idsToDelete)), ',');
        $stmt = $db->prepare("DELETE FROM mood WHERE id IN ($placeholders)");
        $stmt->execute($idsToDelete);
    }
}