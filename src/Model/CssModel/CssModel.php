<?php
class CssModel {
    public static function load(): Array {
        global $db;
        $stmt = $db->prepare("SELECT id, filename, description FROM css ORDER BY filename");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): Array{
       global $db;
       $stmt = $db->prepare("SELECT id, filename, description FROM css WHERE id=?");
       $stmt->execute([$id]);
       return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByFilename(string $filename): Array{
       global $db;
       $stmt = $db->prepare("SELECT id, filename, description FROM css WHERE filename=?");
       $stmt->execute([$filename]);
       return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): bool{
        global $db;
        $stmt = $db->prepare("DELETE FROM css WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function save(string $filename, ?string $description = null): void {
        global $db;

        $stmt = $db->prepare("SELECT COUNT(id) FROM css WHERE filename = ?");
        $stmt->execute([$filename]);
        $fileExists = $stmt->fetchColumn();

        if ($fileExists) {
            $stmt = $db->prepare("UPDATE css SET description = ? WHERE filename = ?");
        } else {
            $stmt = $db->prepare("INSERT INTO css (filename, description) VALUES (?, ?)");
        }

        $stmt->execute([$filename, $description]);
    }
}