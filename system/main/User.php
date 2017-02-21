<?php namespace ys\main;

class User {
    /**
     * Singleton pattern via $users field
     * @var null|object
     */
    private static $users = null;

    // @formatter:off
    protected function __construct() {}
    private function __clone() {}
    private function __wakeup() {}
    // @formatter:on

    public static function loadUsers() {
        if ( ! isset(self::$users)) {
            self::$users = json_decode(file_get_contents(SYSDIR . '/storage/users.json'));
        }

        return self::$users;
    }

    // alias of loadUsers()
    public static function getInstance() {
        return self::loadUsers();
    }

    // another alias :) XD lol
    public static function getUsers() {
        return self::loadUsers();
    }

    /**
     * @param int $uid
     * @return object|null
     */
    public static function getUser($uid) {
        self::loadUsers();

        if (isset(self::$users->$uid)) {
            return self::$users->$uid;
        }

        return null;
    }

    /**
     * @param string $email
     * @param string $pass
     * @return bool
     */
    public static function auth($email, $pass) {
        self::loadUsers();

        foreach (self::$users as $user) {
            if ($user->email === $email) {
                return shash($pass, $user->passhash);
            }
        }

        return false;
    }
}
