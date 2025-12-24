<?php
namespace indura\db\postgresql;

use PDOException;
use Exception;
use PDO;

use indura\validator\Scheme;
use indura\json\Response;

abstract class BaseModel {
    protected $connection;
    protected $table;
    protected $primaryKey;
    protected $fillable = [];
    protected $validationRules = [];
    public $schemeValidator;

    public function __construct($connection) {
        $this->schemeValidator = new Scheme($this->validationRules);
        $this->connection = $connection;
    }

    public function findAll() {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY {$this->primaryKey}";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error getting records:" . $e->getMessage());
        }
    }

    public function findById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error getting record: " . $e->getMessage());
        }
    }

    public function create($data) {
        try {
            $this->validate($data);
            $filteredData = $this->filterFillable($data);
            
            $fields = array_keys($filteredData);
            $placeholders = ':' . implode(', :', $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders}) RETURNING {$this->primaryKey}";
            
            $stmt = $this->connection->prepare($sql);
            
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            $newId = $stmt->fetchColumn();
            
            return $this->findById($newId);
        } catch (PDOException $e) {
            throw new Exception("Error creating record: " . $e->getMessage());
        }
    }

    public function update($id, $data) {
        try {
            $existing = $this->findById($id);
            if (!$existing) {
                throw new Exception("Record not found");
            }
            
            $this->validate($data, $id);
            $filteredData = $this->filterFillable($data);
            
            $setParts = [];
            foreach ($filteredData as $key => $value) {
                $setParts[] = "{$key} = :{$key}";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE {$this->primaryKey} = :id";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            
            return $this->findById($id);
        } catch (PDOException $e) {
            throw new Exception("Error updating record:" . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $existing = $this->findById($id);
            if (!$existing) {
                throw new Exception("Record not found");
            }
            
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("Error deleting record: " . $e->getMessage());
        }
    }

    protected function filterFillable($data) {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function validate($data, $id = null) {
        $errors = $this->schemeValidator->validate($data);
        
        if (!empty($errors)) {
            Response::validation($errors);
        }
    }
}