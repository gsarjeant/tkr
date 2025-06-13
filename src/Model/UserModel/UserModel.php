<?php
class UserModel {
    // properties
    public string $username = '';
    public string $displayName = '';
    public string $about = '';
    public string $website = '';
    public string $mood = '';

    // load user settings from sqlite database
    public static function load(): self {
        global $db;

        // There's only ever one user. I'm just leaning into that.
        $stmt = $db->query("SELECT username, display_name, about, website, mood FROM user WHERE id=1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $u = new self();

        if ($row) {
            $u->username = $row['username'];
            $u->displayName = $row['display_name'];
            $u->about = $row['about'] ?? '';
            $u->website = $row['website'] ?? '';
            $u->mood = $row['mood'] ?? '';
        }

        return $u;
    }

   public function save(): self {
      global $db;
      $userCount = (int) $db->query("SELECT COUNT(*) FROM user")->fetchColumn();

      if ($userCount === 0){
        $stmt = $db->prepare("INSERT INTO user (id, username, display_name, about, website, mood) VALUES (1, ?, ?, ?, ?, ?)");
      } else {
        $stmt = $db->prepare("UPDATE user SET username=?, display_name=?, about=?, website=?, mood=? WHERE id=1");
      }

      $stmt->execute([$this->username, $this->displayName, $this->about, $this->website, $this->mood]);

      return self::load();
   }

   // Making this a separate function to avoid
   // loading the password into memory
   public function set_password(string $password): void {
        global $db;
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE user SET password_hash=? WHERE id=1");
        $stmt->execute([$hash]);
   }
}
