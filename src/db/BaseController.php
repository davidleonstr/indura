<?php
namespace indura\db;

use Exception;

use indura\json\Response;

abstract class BaseController {
    protected $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function index() {
        try {
            $data = $this->model->findAll();
            Response::success($data, 'Records successfully obtained');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

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