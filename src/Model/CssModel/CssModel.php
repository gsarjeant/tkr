<?php
declare(strict_types=1);

class CssModel {
    public function __construct(private PDO $db) {}

    public function getAll(): Array {
        $stmt = $this->db->prepare("SELECT id, filename, description FROM css ORDER BY filename");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getById(int $id): Array{
       $stmt = $this->db->prepare("SELECT id, filename, description FROM css WHERE id=?");
       $stmt->execute([$id]);
       return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByFilename(string $filename): Array{
       $stmt = $this->db->prepare("SELECT id, filename, description FROM css WHERE filename=?");
       $stmt->execute([$filename]);
       return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete(int $id): bool{
        $stmt = $this->db->prepare("DELETE FROM css WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function save(string $filename, ?string $description = null): void {
        $stmt = $this->db->prepare("SELECT COUNT(id) FROM css WHERE filename = ?");
        $stmt->execute([$filename]);
        $fileExists = $stmt->fetchColumn();

        if ($fileExists) {
            $stmt = $this->db->prepare("UPDATE css SET description = ? WHERE filename = ?");
        } else {
            $stmt = $this->db->prepare("INSERT INTO css (filename, description) VALUES (?, ?)");
        }

        $stmt->execute([$filename, $description]);
    }
}