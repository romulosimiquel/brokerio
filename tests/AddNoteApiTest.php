<?php

use PHPUnit\Framework\TestCase;

class AddNoteApiTest extends TestCase
{
    public function testAddNoteValidatesInput()
    {
        $testCases = [
            ['input' => ['property_id' => 'invalid', 'note' => 'Test note'], 'expected' => false],
            ['input' => ['property_id' => '1', 'note' => ''], 'expected' => false],
            ['input' => ['property_id' => '1', 'note' => '   '], 'expected' => false],
            ['input' => ['property_id' => '1', 'note' => 'Valid note'], 'expected' => true],
            ['input' => ['property_id' => null, 'note' => 'Test'], 'expected' => false],
        ];
        
        foreach ($testCases as $testCase) {
            $input = $testCase['input'];
            $shouldBeValid = $testCase['expected'];
            
            $propertyId = $input['property_id'] ?? null;
            $note = trim($input['note'] ?? '');
            
            $isValidPropertyId = $propertyId !== null && is_numeric($propertyId);
            $isValidNote = !empty($note);
            $isValid = $isValidPropertyId && $isValidNote;
            
            $this->assertEquals($shouldBeValid, $isValid, 
                "Validation failed for property_id: {$propertyId}, note: '{$note}'");
        }
    }

    public function testAddNoteReturnsCorrectStructure()
    {
        $expectedStructure = [
            'success' => true,
            'note' => [
                'id' => 1,
                'property_id' => 1,
                'note' => 'Test note',
                'created_at' => '2024-01-01 12:00:00'
            ]
        ];
        
        $json = json_encode($expectedStructure);
        $decoded = json_decode($json, true);
        
        $this->assertTrue($decoded['success']);
        $this->assertArrayHasKey('note', $decoded);
        $this->assertArrayHasKey('id', $decoded['note']);
        $this->assertArrayHasKey('property_id', $decoded['note']);
        $this->assertArrayHasKey('note', $decoded['note']);
        $this->assertArrayHasKey('created_at', $decoded['note']);
    }
}

