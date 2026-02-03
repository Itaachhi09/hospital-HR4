<?php
/**
 * Base Data Transfer Object (DTO)
 * 
 * All DTOs should extend this base class to ensure consistent behavior.
 */

namespace Common\DTO;

abstract class BaseDTO {
    /**
     * Create DTO from array
     * 
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static {
        $dto = new static();
        
        foreach ($data as $key => $value) {
            $property = self::camelCase($key);
            if (property_exists($dto, $property)) {
                $dto->$property = $value;
            }
        }
        
        return $dto;
    }
    
    /**
     * Create DTO from request
     * 
     * @param array $requestData Request data (from $_POST, $_GET, or JSON)
     * @return static
     */
    public static function fromRequest(array $requestData): static {
        return self::fromArray($requestData);
    }
    
    /**
     * Convert DTO to array
     * 
     * @return array
     */
    public function toArray(): array {
        $result = [];
        $reflection = new \ReflectionClass($this);
        
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            
            // Convert property name to snake_case for API
            $key = self::snakeCase($property->getName());
            $result[$key] = $value;
        }
        
        return $result;
    }
    
    /**
     * Convert DTO to JSON
     * 
     * @return string
     */
    public function toJson(): string {
        return json_encode($this->toArray());
    }
    
    /**
     * Validate DTO
     * 
     * @return array Array of validation errors (empty if valid)
     */
    public function validate(): array {
        return [];
    }
    
    /**
     * Convert snake_case to camelCase
     * 
     * @param string $string
     * @return string
     */
    private static function camelCase(string $string): string {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }
    
    /**
     * Convert camelCase to snake_case
     * 
     * @param string $string
     * @return string
     */
    private static function snakeCase(string $string): string {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}
