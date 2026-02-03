<?php
/**
 * HR Core Service Interface
 * 
 * Defines the contract for HR Core services.
 * Other modules should use this interface, not the implementation.
 */

namespace Modules\HRCore\Services\Interfaces;

use Modules\HRCore\DTO\EmployeeDTO;
use Modules\HRCore\DTO\CreateEmployeeDTO;

interface HRCoreServiceInterface {
    /**
     * Get employee by ID
     * 
     * @param int $id Employee ID
     * @return EmployeeDTO|null
     * @throws \Exception
     */
    public function getEmployee(int $id): ?EmployeeDTO;
    
    /**
     * Get employees by department
     * 
     * @param int $departmentId Department ID
     * @param array $filters Additional filters
     * @return EmployeeDTO[]
     * @throws \Exception
     */
    public function getEmployeesByDepartment(int $departmentId, array $filters = []): array;
    
    /**
     * Get all employees with pagination
     * 
     * @param int $page Page number
     * @param int $limit Items per page
     * @param array $filters Filters
     * @return array ['data' => EmployeeDTO[], 'total' => int, 'page' => int, 'limit' => int]
     * @throws \Exception
     */
    public function getEmployees(int $page = 1, int $limit = 20, array $filters = []): array;
    
    /**
     * Create new employee
     * 
     * @param CreateEmployeeDTO $dto
     * @return EmployeeDTO
     * @throws \Exception
     */
    public function createEmployee(CreateEmployeeDTO $dto): EmployeeDTO;
    
    /**
     * Update employee
     * 
     * @param int $id Employee ID
     * @param array $data Update data
     * @return EmployeeDTO
     * @throws \Exception
     */
    public function updateEmployee(int $id, array $data): EmployeeDTO;
    
    /**
     * Delete employee (soft delete)
     * 
     * @param int $id Employee ID
     * @return bool
     * @throws \Exception
     */
    public function deleteEmployee(int $id): bool;
    
    /**
     * Get employee count
     * 
     * @param array $filters Filters
     * @return int
     * @throws \Exception
     */
    public function getEmployeeCount(array $filters = []): int;
}
