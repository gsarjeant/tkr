<?php
declare(strict_types=1);

// welp this model is overkill
class EmojiModel{
    public function __construct(private PDO $db) {}

    // This isn't memory-efficient,
    // but I think it'll be fine on this app's scales.
    public function getAll(): array {
        $stmt = $this->db->query("SELECT id, emoji, description FROM emoji");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // I'm not going to support editing emoji.
    // It'll just be a delete/readd
    public function add(string $emoji, ?string $description): void{
        $stmt = $this->db->prepare("INSERT INTO emoji (emoji, description) VALUES (?, ?)");
        $stmt->execute([$emoji, $description]);
    }

    public function delete(array $idsToDelete): void{
        $placeholders = rtrim(str_repeat('?,', count($idsToDelete)), ',');
        $stmt = $this->db->prepare("DELETE FROM emoji WHERE id IN ($placeholders)");
        $stmt->execute($idsToDelete);
    }
}