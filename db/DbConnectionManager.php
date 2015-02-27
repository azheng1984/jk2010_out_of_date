<?php
namespace Hyperframework\Db;

use InvalidArgumentException;
use Hyperframework\Common\Config;
use Hyperframework\Common\ClassNotFoundException;
use Hyperframework\Common\InvalidOperationException;

class DbConnectionManager {
    private $isConnectionPoolEnabled;
    private $connection;
    private $connectionFactory;
    private $connectionPool = [];

    public function connect($name) {
        if ($this->isConnectionPoolEnabled()) {
            if (isset($this->connectionPool[$name])) {
                $this->connection = $this->connectionPool[$name];
            } else {
                $factory = $this->getConnectionFactory();
                $this->connection = $factory->createConnection($name);
                $this->connectionPool[$name] = $this->connection;
            }
        } else {
            $factory = $this->getConnectionFactory();
            $this->connection = $factory->createConnection($name);
        }
    }

    public function closeConnection($name = null) {
        if ($name === null) {
            if ($this->connection === null) {
                throw new InvalidOperationException(
                    'The current database connection equals null.'
                );
            }
            $name = $this->connection->getName();
            $this->connection = null;
        } elseif ($this->connection !== null) {
            if ($this->connection->getName() === $name) {
                $this->connection = null;
                if ($this->isConnectionPoolEnabled() === false) {
                    return;
                }
            }
        }
        if (isset($this->connectionPool[$name]) === false) {   
            throw new InvalidArgumentException(
                "Argument 'name' is invalid, "
                    . "database connection '$name' does not exist."
            );
        }
        unset($this->connectionPool[$name]);
    }

    public function setConnection($connection) {
        if ($connection === null) {
            $this->connection = $connection;
        } else {
            $this->connection = $connection;
            $connectionName = $connection->getName();
            $this->connectionPool[$connectionName] = $connection;
        }
    }

    public function getConnection($shouldConnect = true) {
        if ($this->connection === null && $shouldConnect) {
            $this->connect('default');
        }
        return $this->connection;
    }

    private function isConnectionPoolEnabled() {
        if ($this->isConnectionPoolEnabled === null) {
            $this->isConnectionPoolEnabled = Config::getBoolean(
                'hyperframework.db.enable_connection_pool', true
            );
        }
        return $this->isConnectionPoolEnabled;
    }

    private function getConnectionFactory() {
        if ($this->connectionFactory === null) {
            $configName = 'hyperframework.db.connection_factory_class';
            $class = Config::getString($configName, '');
            if ($class === '') {
                $this->connectionFactory = new DbConnectionFactory;
            } else {
                if (class_exists($class) === false) {
                    throw new ClassNotFoundException(
                        "Database connection factory Class '$class' does not"
                            . " exist, set using config '$configName'."
                    );
                }
                $this->connectionFactory = new $class;
            }
        }
        return $this->connectionFactory;
    }
}