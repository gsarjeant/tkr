<?php
class User {
    // properties
    public string $username;
    public string $displayName;
    public string $about;
    public string $website;
    public string $mood;

    // load user settings from sqlite database
    public static function load(): self {
        $db = Util::get_db();

        // There's only ever one user. I'm just leaning into that.
        $stmt = $db->query("SELECT username, display_name, about, website, mood FROM user WHERE id=1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $u = new self();

        if ($row) {
            $u->username = $row['username'];
            $u->displayName = $row['display_name'];
            $u->about = $row['about'] ?? '';
            $u->website = $row['website'] ?? '';
            $u->mood = $row['mood'];
        }

        return $u;
    }

   public function save(): self {
      $db = Util::get_db();

      $stmt = $db->prepare("UPDATE user SET username=?, display_name=?, about=?, website=?, mood=? WHERE id=1");
      $stmt->execute([$this->username, $this->displayName, $this->about, $this->website, $this->mood]);

      return self::load();
   }

   // Making this a separate function to avoid
   // loading the password into memory
   public function set_password(string $password): void {
        $db = Util::get_db();
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE user SET password_hash=? WHERE id=1");
        $stmt->execute([$hash]);
   }
}
