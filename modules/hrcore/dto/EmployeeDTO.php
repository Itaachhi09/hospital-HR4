<?php
/**
 * Employee Data Transfer Object
 * 
 * Used for transferring employee data between modules via APIs.
 */

namespace Modules\HRCore\DTO;

use Common\DTO\BaseDTO;

class EmployeeDTO extends BaseDTO {
    public int $id;
    public string $firstName;
    public string $lastName;
    public string $email;
    public ?string $phone = null;
    public int $departmentId;
    public ?string $departmentName = null;
    public ?int $positionId = null;
    public ?string $positionName = null;
    public ?string $employeeNumber = null;
    public ?string $hireDate = null;
    public ?string $status = null;
    
    /**
     * Validate employee DTO
     * 
     * @return array
     */
    public function validate(): array {
        $errors = [];
        
        if (empty($this->firstName)) {
            $errors[] = 'First name is required';
        }
        
        if (empty($this->lastName)) {
            $errors[] = 'Last name is required';
        }
        
        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if ($this->departmentId <= 0) {
            $errors[] = 'Valid department ID is required';
        }
        
        return $errors;
    }
}
