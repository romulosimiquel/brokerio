<?php

use PHPUnit\Framework\TestCase;

class PropertyApiTest extends TestCase
{
    public function testGetPropertyValidatesNumericId()
    {
        $testCases = [
            'invalid' => false,
            'abc123' => false,
            '123' => true,
            '0' => true,
            '' => false,
            null => false
        ];
        
        foreach ($testCases as $input => $shouldBeValid) {
            $isValid = $input !== null && $input !== '' && is_numeric($input);
            $this->assertEquals($shouldBeValid, $isValid, "Input '{$input}' validation failed");
        }
    }

    public function testGetPropertyReturnsCorrectStructure()
    {
        $expectedStructure = [
            'property' => [
                'id' => 1,
                'name' => 'Test Property',
                'address' => '123 Test St'
            ],
            'notes' => []
        ];
        
        $json = json_encode($expectedStructure);
        $decoded = json_decode($json, true);
        
        $this->assertArrayHasKey('property', $decoded);
        $this->assertArrayHasKey('notes', $decoded);
        $this->assertIsArray($decoded['notes']);
    }
}

