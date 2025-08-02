<?php
class UserModel {
    // properties
    public string $username = '';
    public string $displayName = '';
    public string $website = '';
    public string $mood = '';

    public function __construct(private PDO $db) {}

    // load user settings from sqlite database (backward compatibility)
    public static function load(): self {
        global $db;
        $instance = new self($db);
        return $instance->loadFromDatabase();
    }
    
    // Instance method that uses injected database
    public function loadFromDatabase(): self {
        // There's only ever one user. I'm just leaning into that.
        $stmt = $this->db->query("SELECT username, display_name, website, mood FROM user WHERE id=1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $u = new self($this->db);

        if ($row) {
            $u->username = $row['username'];
            $u->displayName = $row['display_name'];
            $u->website = $row['website'] ?? '';
            $u->mood = $row['mood'] ?? '';
        }

        return $u;
    }

   public function save(): self {
      $userCount = (int) $this->db->query("SELECT COUNT(*) FROM user")->fetchColumn();

      if ($userCount === 0){
        $stmt = $this->db->prepare("INSERT INTO user (id, username, display_name, website, mood) VALUES (1, ?, ?, ?, ?)");
      } else {
        $stmt = $this->db->prepare("UPDATE user SET username=?, display_name=?, website=?, mood=? WHERE id=1");
      }

      $stmt->execute([$this->username, $this->displayName, $this->website, $this->mood]);

      return $this->loadFromDatabase();
   }

   // Making this a separate function to avoid
   // loading the password into memory
   public function setPassword(string $password): void {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE user SET password_hash=? WHERE id=1");
        $stmt->execute([$hash]);
   }

   public function getByUsername($username){
        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM user WHERE username = ?");
        $stmt->execute([$username]);
        $record = $stmt->fetch();

        return $record;
   }
}
