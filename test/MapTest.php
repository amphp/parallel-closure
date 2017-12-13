<?php

namespace Amp\ParallelFunctions\Test;

use Amp\MultiReasonException;
use Amp\PHPUnit\TestCase;
use function Amp\ParallelFunctions\map;
use function Amp\Promise\wait;

class MapTest extends TestCase {
    public function testValidInput() {
        $this->assertSame([3, 4, 5], wait(map([1, 2, 3], function ($input) {
            return $input + 2;
        })));
    }

    public function testException() {
        $this->expectException(MultiReasonException::class);

        wait(map([1, 2, 3], function () {
            throw new \Exception;
        }));
    }

    public function testExecutesAllTasksOnException() {
        $files = [
            [0, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [1, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
            [2, \tempnam(\sys_get_temp_dir(), 'amp-parallel-functions-')],
        ];

        try {
            wait(map($files, function ($args) {
                list($id, $filename) = $args;

                if ($id === 0) {
                    throw new \Exception;
                }

                \sleep(1);
                \file_put_contents($filename, $id);
            }));

            $this->fail('No exception thrown.');
        } catch (MultiReasonException $e) {
            $this->assertStringEqualsFile($files[1][1], '1');
            $this->assertStringEqualsFile($files[2][1], '2');
        }
    }
}
