<?php
namespace indura\db\postgresql;

use PDOException;
use Exception;
use PDO;

use indura\validator\Scheme;
use indura\json\Response;

/**
 * BaseModel
 * 
 * Abstract base class for PostgreSQL database models.
 * Provides CRUD operations, validation, and field filtering functionality.
 * Child classes should define table name, primary key, fillable fields, and validation rules.
 */
abstract class BaseModel {
    protected $connection;
    protected $table;
    protected $primaryKey;
    protected $fillable = [];
    protected $validationRules = [];
    public $schemeValidator;

    /**
     * Constructor
     * 
     * Initializes the model with a database connection and sets up the validation scheme.
     * 
     * @param PDO $connection Database connection instance
     */
    public function __construct($connection) {
        $this->schemeValidator = new Scheme($this->validationRules);
        $this->connection = $connection;
    }

    /**
     * Retrieves all records from the table
     * 
     * Fetches all records ordered by the primary key.
     * 
     * @return array Array of records as associative arrays
     * @throws Exception If database query fails
     */
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

    /**
     * Retrieves a single record by its primary key
     * 
     * @param int $id The primary key value
     * @return array|false Associative array of the record or false if not found
     * @throws Exception If database query fails
     */
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

    /**
     * Creates a new record in the table
     * 
     * Validates data, filters to fillable fields only, and inserts the record.
     * Returns the newly created record including its generated primary key.
     * 
     * @param array $data Associative array of field names and values
     * @return array The newly created record
     * @throws Exception If validation fails or database operation fails
     */
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

    /**
     * Updates an existing record
     * 
     * Validates the record exists, validates data, filters to fillable fields,
     * and updates the record. Returns the updated record.
     * 
     * @param int $id The primary key value of the record to update
     * @param array $data Associative array of field names and values to update
     * @return array The updated record
     * @throws Exception If record not found, validation fails, or database operation fails
     */
    public function update($id, $data) {
        try {
            $existing = $this->findById($id);
            if (!$existing) {
                throw new Exception("Record not found");
            }
            
            $this->validate($data);
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

    /**
     * Deletes a record from the table
     * 
     * Validates the record exists before attempting deletion.
     * 
     * @param int $id The primary key value of the record to delete
     * @return bool True if deletion was successful
     * @throws Exception If record not found or database operation fails
     */
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

    /**
     * Filters data to only include fillable fields
     * 
     * Protects against mass-assignment vulnerabilities by only allowing
     * fields defined in the $fillable property.
     * 
     * @param array $data Associative array of data to filter
     * @return array Filtered array containing only fillable fields
     */
    protected function filterFillable($data) {
        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Validates data against the model's validation rules
     * 
     * Uses the Scheme validator to validate data. If validation fails,
     * sends a validation error response and halts execution.
     * 
     * @param array $data Associative array of data to validate
     * @return void
     * @throws void Sends validation response and exits if validation fails
     */
    protected function validate($data) {
        $errors = $this->schemeValidator->validate($data);
        
        if (!empty($errors)) {
            Response::validation($errors);
        }
    }
}