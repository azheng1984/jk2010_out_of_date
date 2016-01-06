<?php
namespace Hyperframework\Db;

use Closure;
use InvalidArgumentException;
use Hyperframework\Common\Config;
use Hyperframework\Common\Validator;
use Hyperframework\Common\NotImplementedException;

abstract class DbActiveRecord {
    private static $tableNames = [];
    private static $metadata;
    private $row;
    private $validationErrors;

    /**
     * @param array $row
     */
    public function __construct() {
        $this->initializeRow();
    }

    /**
     * @param array|string $where
     * @param array $params
     * @return static
     */
    public static function find($where, array $params = null) {
        if (is_array($where)) {
            $row = DbClient::findRowByColumns(static::getTableName(), $where);
        } elseif (is_string($where) || $where === null) {
            $row = DbClient::findRow(
                self::completeSelectSql($where),
                $params
            );
        } else {
            $type = gettype($where);
            throw new InvalidArgumentException(
                "Argument 'where' must be a string or an array, $type given."
            );
        }
        if ($row === false) {
            return;
        }
        return static::build($row);
    }

    /**
     * @param int $id
     * @return static
     */
    public static function findById($id) {
        $row = DbClient::findRowById(static::getTableName(), $id);
        if ($row === false) {
            return;
        }
        return static::build($row);
    }

    /**
     * @param array $columns
     * @return static
     */
    public static function findByColumns($columns) {
        $row = DbClient::findRowByColumns(static::getTableName(), $columns);
        if ($row === false) {
            return;
        }
        return static::build($row);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return static
     */
    public static function findBySql($sql, array $params = null) {
        $row = DbClient::findRow($sql, $params);
        if ($row === false) {
            return;
        }
        return static::build($row);
    }

    /**
     * @param array|string $where
     * @param array $params
     * @return static[]
     */
    public static function findAll($where = null, array $params = null) {
        if (is_array($where)) {
            $rows = DbClient::findAllByColumns(static::getTableName(), $where);
        } elseif (is_string($where) || $where === null) {
            $rows = DbClient::findAll(
                self::completeSelectSql($where), $params
            );
        } else {
            $type = gettype($where);
            throw new InvalidArgumentException(
                "Argument 'where' must be a string or an array, $type given."
            );
        }
        $result = [];
        foreach ($rows as $row) {
            $result[] = static::build($row);
        }
        return $result;
    }

    /**
     * @param array $columns
     * @return static[]
     */
    public static function findAllByColumns(array $columns) {
        $rows = DbClient::findAllByColumns(static::getTableName(), $columns);
        $result = [];
        foreach ($rows as $row) {
            $result[] = static::build($row);
        }
        return $result;
    }

    /**
     * @param string $sql
     * @param array $params
     * @return static[]
     */
    public static function findAllBySql($sql, array $params = null) {
        $rows = DbClient::findAll($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[] = static::build($row);
        }
        return $result;
    }

    /**
     * @param array|string $where
     * @param array $params
     * @return int
     */
    public static function count($where = null, array $params = null) {
        return DbClient::count(static::getTableName(), $where, $params);
    }

    /**
     * @param string $columnName
     * @param array|string $where
     * @param array $params
     * @return mixed
     */
    public static function min(
        $columnName, $where = null, array $params = null
    ) {
        return DbClient::min(
            static::getTableName(),
            $columnName,
            $where,
            $params
        );
    }

    /**
     * @param string $columnName
     * @param array|string $where
     * @param array $params
     * @return mixed
     */
    public static function max(
        $columnName, $where = null, array $params = null
    ) {
        return DbClient::max(
            static::getTableName(),
            $columnName,
            $where,
            $params
        );
    }

    /**
     * @param string $columnName
     * @param array|string $where
     * @param array $params
     * @return mixed
     */
    public static function sum(
        $columnName, $where = null, array $params = null
    ) {
        return DbClient::sum(
            static::getTableName(),
            $columnName,
            $where,
            $params
        );
    }

    /**
     * @param string $columnName
     * @param array|string $where
     * @param array $params
     * @return mixed
     */
    public static function average(
        $columnName, $where = null, array $params = null
    ) {
        return DbClient::average(
            static::getTableName(),
            $columnName,
            $where,
            $params
        );
    }

    /**
     * @return string
     */
    public static function getTableName() {
        $class = get_called_class();
        if (isset(self::$tableNames[$class]) === false) {
            if (empty(static::getMetadata()['table']) === false) {
                self::$tableNames[$class] =
                    (string)static::getMetadata()['table'];
            } else {
                $position = strrpos($class, '\\');
                if ($position !== false) {
                    self::$tableNames[$class] = substr($class, $position + 1);
                }
            }
        }
        return self::$tableNames[$class];
    }

    /**
     * @return array
     */
    public static function getMetadata() {
        if (self::$metadata === null) {
            self::$metadata = static::buildMetadata();
            if (is_array(self::$metadata) === false) {
                $type = gettype(self::$metadata);
                throw new DbActiveRecordException(
                    "The metadata of active record '" . get_called_class()
                        . "' must be an array, $type given."
                );
            } elseif (isset(self::$metadata['columns']) === false) {
                throw new DbActiveRecordException(
                    "The metadata of active record '" . get_called_class()
                        . "' is invalid, field 'columns' is missing."
                );
            } elseif (isset(self::$metadata['columns']['id']) === false) {
                throw new DbActiveRecordException(
                    "The metadata of active record '" . get_called_class()
                        . "' is invalid, column 'id' is missing."
                );
            }
        }
        return self::$metadata;
    }

    public function insert() {
        DbClient::insert($this->getTableName(), $this->getRow());
        if ($this->getColumn('id') === null) {
            $this->setColumn('id', DbClient::getLastInsertId());
        }
    }

    public function update() {
        if ($this->getColumn('id') === null) {
            $class = get_called_class();
            throw new DbActiveRecordException(
                "Cannot update active record '$class' which is not persistent, "
                    . "because column 'id' is null."
            );
        }
        $row = $this->getRow();
        if (count($row) === 1) {
            return;
        }
        unset($row['id']);
        DbClient::updateById(
            static::getTableName(), $row, $this->getColumn('id')
        );
    }

    public function delete() {
        $id = $this->getColumn('id');
        if ($id !== null) {
            DbClient::deleteById(static::getTableName(), $id);
        } else {
            $class = get_called_class();
            throw new DbActiveRecordException(
                "Cannot delete active record '$class' which is not persistent, "
                    . "because column 'id' is null."
            );
        }
    }

    public function save() {
        if ($this->getColumn('id') === null) {
            $this->insert();
        } else {
            $this->update();
        }
    }

    /**
     * @return bool
     */
    public function isValid() {
        $validationErrors = $this->validate();
        $this->setValidationErrors($validationErrors);
        return $validationErrors === null;
    }

    /**
     * @return array
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }

    /**
     * @param array $validationErrors
     */
    public function setValidationErrors(array $validationErrors = null) {
        $this->validationErrors = $validationErrors;
    }

    /**
     * @return array
     */
    public function toArray() {
        return $this->getRow();
    }

    /**
     * @return array
     */
    protected static function buildMetadata() {
        throw new NotImplementedException(
            "Method '" . get_called_class()
                . "::buildMetadata' has not been implemented."
        );
    }

    /**
     * @param array $row
     * @return static
     */
    protected static function build(array $row) {
        $result = new static;
        $result->setColumns($row);
        return $result;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function validate(array $options = []) {
        $columnNames = null;
        if (isset($options['only'])) {
            $columnNames = $options['only'];
        }
        $metadata = static::getMetadata()['columns'];
        $result = [];
        foreach ($metadata as $columnName => $columnMetadata) {
            if ($columnNames !== null) {
                if (in_array($columnName, $columnNames, true) === false) {
                    continue;
                }
            }
            $type = Validator::getRuleValue($columnMetadata, 'type');
            if ($type === null) {
                throw new DbActiveRecordException(
                    "The metadata of active record '" . get_called_class()
                        . "' is invalid, the type of column '" . $columnName
                        . "' is undefined."
                );
            }
            $validator = null;
            $validators = static::getValidators();
            if (isset($validators[$type])) {
                $validator = $validators[$type];
            } else {
                $types = [
                    'int'      => 'Hyperframework\Common\IntegerValidator',
                    'float'    => 'Hyperframework\Common\FloatValidator',
                    'decimal'  => 'Hyperframework\Common\DecimalValidator',
                    'string'   => 'Hyperframework\Common\StringValidator',
                    'date'     => 'Hyperframework\Common\DateValidator',
                    'time'     => 'Hyperframework\Common\TimeValidator',
                    'datetime' => 'Hyperframework\Common\DateTimeValidator',
                    'url'      => 'Hyperframework\Common\UrlValidator',
                    'email'    => 'Hyperframework\Common\EmailValidator',
                ];
                if (isset($types[$type])) {
                    $validator = $types[$type];
                }
            }
            if ($validator !== null) {
                $columnValue = $this->getColumn($columnName);
                $options = [
                    'target_name' => "column '$columnName' of active record '"
                        . get_called_class() . "'",
                    'metadata_name' => "the metadata of column '$columnName'"
                        . " in active record '" . get_called_class() . "'"
                ];
                $validationError = $validator::validate(
                    $columnValue, $columnMetadata, $options
                );
                if ($validationError !== null) {
                    $result[$columnName] = $validationError;
                }
            }
        }
        if (count($result) !== 0) {
            return $result;
        }
    }

    /**
     * @return array
     */
    protected static function getValidators() {
        return Config::getArray(
            'hyperframework.db.active_record.validators', []
        );
    }

    /**
     * @return array
     */
    protected function getRow() {
        return $this->row;
    }

    /**
     * @param array $columns
     * @param array $columnNames
     */
    protected function setColumns(array $columns) {
        foreach ($columns as $name => $value) {
           $this->setColumn($name, $value);
        }
    }

    /**
     * @param mixed $columnName
     * @return mixed
     */
    protected function getColumn($columnName) {
        if ($this->hasColumn($columnName) === false) {
            throw new DbActiveRecordException(
                $this->getInvalidColumnNameErrorMessage($columnName)
            );
        }
        return $this->row[$columnName];
    }

    /**
     * @param mixed $columnName
     * @param mixed $value
     */
    protected function setColumn($columnName, $value) {
        if ($this->hasColumn($columnName) === false) {
            throw new DbActiveRecordException(
                $this->getInvalidColumnNameErrorMessage($columnName)
            );
        }
        $this->row[$columnName] = $value;
    }

    /**
     * @param string $columnName
     * @return bool
     */
    protected function hasColumn($columnName) {
        return isset(static::getMetadata()['columns'][$columnName]);
    }

    /**
     * @param string $where
     * @return string
     */
    private static function completeSelectSql($where) {
        $result = 'SELECT * FROM '
            . DbClient::quoteIdentifier(static::getTableName());
        $where = (string)$where;
        if ($where !== '') {
            $result .= ' WHERE ' . $where;
        }
        return $result;
    }

    private function initializeRow() {
        $this->row = [];
        $metadata = static::getMetadata()['columns'];
        foreach ($metadata as $columnName => $columnMetadata) {
            if (isset($columnMetadata['default'])) {
                if ($columnMetadata['default'] instanceof Closure) {
                    $callback = $columnMetadata['default'];
                    $this->setColumn($columnName, $callback());
                } else {
                    $this->setColumn($columnName, $columnMetadata['default']);
                }
            } else {
                $this->setColumn($columnName, null);
            }
        }
    }

    /**
     * @param string $columnName
     * @return string
     */
    private function getInvalidColumnNameErrorMessage($columnName) {
        return "Column '$columnName' does not exist in active record '"
            . get_called_class() . "'.";
    }
}
