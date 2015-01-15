<?php
namespace Hyperframework\Db;

use PDO;
use Exception;
use Hyperframework\ConfigFileLoader;

class DbConnectionFactory {
    private static $config;

    public static function build($name = 'default') {
        $config = self::getConfig($name);
        if (isset($config['dsn']) === false) {
            throw new Exception;
        }
        $username = isset($config['username']) ? $config['username'] : null;
        $password = isset($config['password']) ? $config['password'] : null;
        $options = isset($config['options']) ? $config['options'] : null;
        $connection = self::getConnection(
            $name, $config['dsn'], $username, $password, $options
        );
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $connection;
    }

    protected static function getConnection(
        $name, $dsn, $username, $password, $options
    ) {
        return new DbConnection($name, $dsn, $username, $password, $options);
    }

    private static function getConfig($name) {
        if (self::$config === null) {
            self::initializeConfig();
        }
        if ($name === 'default' && isset(self::$config['dsn'])
            && is_string(self::$config['dsn'])
        ) {
            return self::$config;
        }
        if (isset(self::$config[$name])) {
            return self::$config[$name];
        }
        throw new Exception("Database config '$name' not found");
    }

    private static function initializeConfig() {
        self::$config = ConfigFileLoader::loadPhp(
            'db.php', 'hyperframework.db.config_path'
        );
        if (self::$config === null) {
            throw new Exception;
        }
    }
}
