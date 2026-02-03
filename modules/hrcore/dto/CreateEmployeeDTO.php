<?php
/**
 * Create Employee Data Transfer Object
 * 
 * Used for creating new employees via API.
 */

namespace Modules\HRCore\DTO;

use Common\DTO\BaseDTO;

class CreateEmployeeDTO extends BaseDTO {
    public string $firstName;
    public string $lastName;
    public string $email;
    public ?string $phone = null;
    public int $departmentId;
    public ?int $positionId = null;
    public ?string $employeeNumber = null;
    public ?string $hireDate = null;
    public ?string $status = 'active';
    
    /**
     * Validate create employee DTO
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
        
        if (empty($this->email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if ($this->departmentId <= 0) {
            $errors[] = 'Valid department ID is required';
        }
        
        return $errors;
    }
}
