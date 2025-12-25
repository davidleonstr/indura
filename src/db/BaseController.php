<?php
namespace indura\db;

use Exception;

use indura\json\Response;

/**
 * BaseController
 * 
 * Abstract base class for RESTful API controllers.
 * Provides standard CRUD operations (index, show, store, update, destroy)
 * that interact with a model and return JSON responses.
 * Child classes should inject their specific model instance.
 */
abstract class BaseController {
    protected $model;

    /**
     * Constructor
     * 
     * Initializes the controller with a model instance.
     * 
     * @param object $model Model instance that handles database operations
     */
    public function __construct($model) {
        $this->model = $model;
    }

    /**
     * Lists all records
     * 
     * Retrieves and returns all records from the model.
     * 
     * @return void Sends JSON response with all records
     */
    public function index() {
        try {
            $data = $this->model->findAll();
            Response::success($data, 'Records successfully obtained');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Shows a single record
     * 
     * Retrieves and returns a specific record by its ID.
     * Returns 404 response if record is not found.
     * 
     * @param int $id The primary key value of the record to retrieve
     * @return void Sends JSON response with the record or error
     */
    public function show($id) {
        try {
            $data = $this->model->findById($id);
            if (!$data) {
                Response::notFound('Record not found');
            }
            Response::success($data, 'Registration successfully obtained');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Creates a new record
     * 
     * Reads JSON data from request body, validates it, and creates a new record.
     * Returns the newly created record with 201 status code.
     * 
     * @return void Sends JSON response with created record or error
     */
    public function store() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                Response::error('Invalid JSON data', 400);
            }
            
            $data = $this->model->create($input);
            Response::success($data, 'Record created successfully', 201);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Updates an existing record
     * 
     * Reads JSON data from request body and updates the specified record.
     * Returns the updated record.
     * 
     * @param int $id The primary key value of the record to update
     * @return void Sends JSON response with updated record or error
     */
    public function update($id) {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                Response::error('Invalid JSON data', 400);
            }
            
            $data = $this->model->update($id, $input);
            Response::success($data, 'Registration successfully updated');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Deletes a record
     * 
     * Removes the specified record from the database.
     * Returns success message if deletion is successful.
     * 
     * @param int $id The primary key value of the record to delete
     * @return void Sends JSON response confirming deletion or error
     */
    public function destroy($id) {
        try {
            $result = $this->model->delete($id);
            if ($result) {
                Response::success(null, 'Record deleted successfully');
            } else {
                Response::error('The record could not be deleted', 500);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}