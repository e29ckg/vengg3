<?php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_basic_math_works()
    {
        // Assert (ยืนยัน) ว่า 1 + 1 ต้องเท่ากับ 2
        $this->assertEquals(2, 1 + 1, "คณิตศาสตร์พื้นฐานทำงานผิดพลาด!");
    }
}