<?php

namespace CodeBes\GrippSdk\Tests\Unit;

use CodeBes\GrippSdk\Resources\AbsenceRequest;
use CodeBes\GrippSdk\Resources\Company;
use CodeBes\GrippSdk\Resources\Cost;
use CodeBes\GrippSdk\Resources\Employee;
use CodeBes\GrippSdk\Resources\EmployeeTarget;
use CodeBes\GrippSdk\Resources\EmployeeYearlyLeaveBudget;
use CodeBes\GrippSdk\Resources\File;
use CodeBes\GrippSdk\Resources\Invoice;
use CodeBes\GrippSdk\Resources\Memorial;
use CodeBes\GrippSdk\Resources\MemorialLine;
use CodeBes\GrippSdk\Resources\Notification;
use CodeBes\GrippSdk\Resources\RevenueTarget;
use CodeBes\GrippSdk\Resources\Task;
use CodeBes\GrippSdk\Resources\UmbrellaProject;
use CodeBes\GrippSdk\Resources\YearTarget;
use CodeBes\GrippSdk\Resources\YearTargetType;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function test_full_crud_resources_have_all_methods(): void
    {
        $fullCrudClasses = [
            AbsenceRequest::class,
            Company::class,
            Employee::class,
            Invoice::class,
            Task::class,
        ];

        foreach ($fullCrudClasses as $class) {
            $this->assertTrue(method_exists($class, 'get'), "{$class} should have get()");
            $this->assertTrue(method_exists($class, 'find'), "{$class} should have find()");
            $this->assertTrue(method_exists($class, 'all'), "{$class} should have all()");
            $this->assertTrue(method_exists($class, 'where'), "{$class} should have where()");
            $this->assertTrue(method_exists($class, 'create'), "{$class} should have create()");
            $this->assertTrue(method_exists($class, 'update'), "{$class} should have update()");
            $this->assertTrue(method_exists($class, 'delete'), "{$class} should have delete()");
        }
    }

    public function test_read_only_resources_lack_write_methods(): void
    {
        $readOnlyClasses = [
            Cost::class,
            EmployeeTarget::class,
            Memorial::class,
            MemorialLine::class,
            RevenueTarget::class,
            YearTarget::class,
            YearTargetType::class,
        ];

        foreach ($readOnlyClasses as $class) {
            $this->assertTrue(method_exists($class, 'get'), "{$class} should have get()");
            $this->assertTrue(method_exists($class, 'find'), "{$class} should have find()");
            $this->assertFalse(method_exists($class, 'create'), "{$class} should NOT have create()");
            $this->assertFalse(method_exists($class, 'update'), "{$class} should NOT have update()");
            $this->assertFalse(method_exists($class, 'delete'), "{$class} should NOT have delete()");
        }
    }

    public function test_no_delete_resources_lack_delete_method(): void
    {
        $noDeleteClasses = [
            EmployeeYearlyLeaveBudget::class,
            UmbrellaProject::class,
        ];

        foreach ($noDeleteClasses as $class) {
            $this->assertTrue(method_exists($class, 'get'), "{$class} should have get()");
            $this->assertTrue(method_exists($class, 'create'), "{$class} should have create()");
            $this->assertTrue(method_exists($class, 'update'), "{$class} should have update()");
            $this->assertFalse(method_exists($class, 'delete'), "{$class} should NOT have delete()");
        }
    }

    public function test_company_has_special_methods(): void
    {
        $this->assertTrue(method_exists(Company::class, 'getCompanyByCOC'));
        $this->assertTrue(method_exists(Company::class, 'addInteractionByCompanyId'));
        $this->assertTrue(method_exists(Company::class, 'addInteractionByCompanyCOC'));
    }

    public function test_employee_has_special_methods(): void
    {
        $this->assertTrue(method_exists(Employee::class, 'getWorkingHours'));
    }

    public function test_invoice_has_special_methods(): void
    {
        $this->assertTrue(method_exists(Invoice::class, 'getViewonlineUrl'));
        $this->assertTrue(method_exists(Invoice::class, 'markAsSent'));
    }

    public function test_file_has_special_methods(): void
    {
        $this->assertTrue(method_exists(File::class, 'get'));
        $this->assertTrue(method_exists(File::class, 'getContent'));
        $this->assertTrue(method_exists(File::class, 'uploadContent'));
        $this->assertFalse(method_exists(File::class, 'create'));
        $this->assertFalse(method_exists(File::class, 'update'));
        $this->assertFalse(method_exists(File::class, 'delete'));
    }

    public function test_notification_has_only_emit_methods(): void
    {
        $this->assertTrue(method_exists(Notification::class, 'emit'));
        $this->assertTrue(method_exists(Notification::class, 'emitall'));
        $this->assertFalse(method_exists(Notification::class, 'get'));
        $this->assertFalse(method_exists(Notification::class, 'create'));
        $this->assertFalse(method_exists(Notification::class, 'update'));
        $this->assertFalse(method_exists(Notification::class, 'delete'));
    }

    public function test_entity_names_are_correct(): void
    {
        // Use reflection to test protected entity() method
        $cases = [
            Task::class => 'task',
            Company::class => 'company',
            Cost::class => 'cost',
            Invoice::class => 'invoice',
            Employee::class => 'employee',
        ];

        foreach ($cases as $class => $expected) {
            $reflection = new \ReflectionMethod($class, 'entity');
            $this->assertEquals($expected, $reflection->invoke(null), "{$class} entity should be '{$expected}'");
        }
    }
}
