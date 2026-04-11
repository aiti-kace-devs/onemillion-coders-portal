<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Api\RegistrationFormAPIController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RegistrationFormAPIControllerTest extends TestCase
{
    public function test_it_groups_schema_fields_by_group_name_without_changing_grouped_field_shape(): void
    {
        $controller = new RegistrationFormAPIController;

        $normalizeSchema = new ReflectionMethod($controller, 'normalizeSchema');
        $normalizeSchema->setAccessible(true);

        $buildGroupedSchema = new ReflectionMethod($controller, 'buildGroupedSchema');
        $buildGroupedSchema->setAccessible(true);

        $schema = $normalizeSchema->invoke($controller, [
            [
                'title' => 'First Name',
                'type' => 'text',
                'description' => 'Your first name as appears on official documents',
                'placeholder' => 'Enter your first name',
                'group_name' => 'Basic Information',
                'rules' => "regex:/^[\\pL\\s\\-\\']+$/u|min:3|max:255",
                'options' => null,
                'validators' => [
                    'required' => true,
                    'unique' => false,
                ],
            ],
            [
                'title' => 'Email',
                'type' => 'email',
                'description' => null,
                'placeholder' => 'Enter your email',
                'group_name' => 'Verification',
                'rules' => 'email:rfc,dns,filter',
                'options' => null,
                'validators' => [
                    'required' => true,
                    'unique' => true,
                ],
            ],
            [
                'title' => 'Course',
                'type' => 'select_course',
                'description' => null,
                'placeholder' => 'Select course',
                'rules' => null,
                'options' => null,
                'validators' => [
                    'required' => false,
                    'unique' => false,
                ],
            ],
        ]);

        $this->assertSame('Enter your first name', $schema[0]['placeholder']);
        $this->assertSame('Basic Information', $schema[0]['group_name']);
        $this->assertSame('first_name', $schema[0]['field_name']);

        $groupedSchema = $buildGroupedSchema->invoke($controller, $schema);

        $this->assertSame([
            [
                'title' => 'Basic Information',
                'fields' => [
                    [
                        'title' => 'First Name',
                        'type' => 'text',
                        'description' => 'Your first name as appears on official documents',
                        'rules' => "regex:/^[\\pL\\s\\-\\']+$/u|min:3|max:255",
                        'options' => null,
                        'validators' => [
                            'required' => true,
                            'unique' => false,
                        ],
                        'field_name' => 'first_name',
                    ],
                ],
            ],
            [
                'title' => 'Verification',
                'fields' => [
                    [
                        'title' => 'Email',
                        'type' => 'email',
                        'description' => null,
                        'rules' => 'email:rfc,dns,filter',
                        'options' => null,
                        'validators' => [
                            'required' => true,
                            'unique' => true,
                        ],
                        'field_name' => 'email',
                    ],
                ],
            ],
            [
                'title' => 'Other',
                'fields' => [
                    [
                        'title' => 'Course',
                        'type' => 'select_course',
                        'description' => null,
                        'rules' => null,
                        'options' => null,
                        'validators' => [
                            'required' => false,
                            'unique' => false,
                        ],
                        'field_name' => 'course',
                    ],
                ],
            ],
        ], $groupedSchema);
    }
}
