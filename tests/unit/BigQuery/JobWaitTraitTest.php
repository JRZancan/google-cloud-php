<?php
/**
 * Copyright 2017 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Cloud\Tests\Unit\BigQuery;

use Google\Cloud\BigQuery\Job;
use Google\Cloud\BigQuery\JobWaitTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group bigquery
 */
class JobWaitTraitTest extends TestCase
{
    private $trait;
    private $job;

    public function setUp()
    {
        $this->trait = \Google\Cloud\Dev\impl(JobWaitTrait::class);
        $this->job = $this->prophesize(Job::class)->reveal();
    }

    public function testWaitSucceedsWhenAlreadyComplete()
    {
        $isCompleteCalled = false;
        $isReloadCalled = false;

        $this->trait->call('wait', [
            function() use (&$isCompleteCalled) {
                $isCompleteCalled = true;
                return true;
            },
            function() use (&$isReloadCalled) {
                $isReloadCalled = true;
                return ['complete' => true];
            },
            $this->job,
            1
        ]);

        $this->assertTrue($isCompleteCalled);
        $this->assertFalse($isReloadCalled);
    }

    public function testWaitCallsReloadThenSucceeds()
    {
        $isCompleteCallCount = 0;
        $isReloadCalled = false;

        $this->trait->call('wait', [
            function() use (&$isCompleteCallCount, &$isReloadCalled) {
                $isCompleteCallCount++;
                return $isReloadCalled ? true : false;
            },
            function() use (&$isReloadCalled) {
                $isReloadCalled = true;
                return ['complete' => true];
            },
            $this->job,
            1
        ]);

        $this->assertEquals(2, $isCompleteCallCount);
        $this->assertTrue($isReloadCalled);
    }

    /**
     * @expectedException Google\Cloud\BigQuery\Exception\JobException
     * @expectedExceptionMessage Job did not complete within the allowed number of retries.
     */
    public function testWaitThrowsExceptionWhenMaxAttemptsMet()
    {
        $this->trait->call('wait', [
            function() { return false; },
            function () { return ['complete' => false]; },
            $this->job,
            1
        ]);
    }
}
