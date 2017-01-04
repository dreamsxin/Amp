<?php

namespace Amp\Test;

use Amp;
use Amp\{ Failure, Pause, Success };
use Interop\Async\Loop;

class StreamTest extends \PHPUnit_Framework_TestCase {
    public function testSuccessfulPromises() {
        $results = [];
        Loop::execute(function () use (&$results) {
            $stream = Amp\stream([new Success(1), new Success(2), new Success(3)]);
    
            $stream->listen(function ($value) use (&$results) {
                $results[] = $value;
            });
        });
        
        $this->assertSame([1, 2, 3], $results);
    }
    
    public function testFailedPromises() {
        $exception = new \Exception;
        Loop::execute(function () use (&$reason, $exception) {
            $stream = Amp\stream([new Failure($exception), new Failure($exception)]);
            
            $callback = function ($exception, $value) use (&$reason) {
                $reason = $exception;
            };
    
            $stream->when($callback);
        });
        
        $this->assertSame($exception, $reason);
    }
    
    public function testMixedPromises() {
        $exception = new \Exception;
        $results = [];
        Loop::execute(function () use (&$results, &$reason, $exception) {
            $stream = Amp\stream([new Success(1), new Success(2), new Failure($exception), new Success(4)]);
    
            $stream->listen(function ($value) use (&$results) {
                $results[] = $value;
            });
            
            $callback = function ($exception, $value) use (&$reason) {
                $reason = $exception;
            };
            
            $stream->when($callback);
        });
        
        $this->assertSame(\range(1, 2), $results);
        $this->assertSame($exception, $reason);
    }
    
    public function testPendingPromises() {
        $results = [];
        Loop::execute(function () use (&$results) {
            $stream = Amp\stream([new Pause(30, 1), new Pause(10, 2), new Pause(20, 3), new Success(4)]);
            
            $stream->listen(function ($value) use (&$results) {
                $results[] = $value;
            });
        });
        
        $this->assertSame(\range(1, 4), $results);
    }
    
    public function testTraversable() {
        $results = [];
        Loop::execute(function () use (&$results) {
            $generator = (function () {
                foreach (\range(1, 4) as $value) {
                    yield $value;
                }
            })();
            
            $stream = Amp\stream($generator);
            
            $stream->listen(function ($value) use (&$results) {
                $results[] = $value;
            });
        });
    
        $this->assertSame(\range(1, 4), $results);
    }
}