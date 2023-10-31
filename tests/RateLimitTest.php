<?php

require '../src/RateLimit.php';

use PHPUnit\Framework\TestCase;

class RateLimitTest extends TestCase
{
    public function testConsumingWithinLimit()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time(), 'invocationCount' => 1];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 5);

        $this->assertTrue($isAllowed);
        $this->assertEquals(5, $updatedCreditState['remainingCredits']);
    }

    public function testConsumingBeyondLimit()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time(), 'invocationCount' => 1];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 100);

        $this->assertFalse($isAllowed);
        $this->assertEquals(10, $updatedCreditState['remainingCredits']);
    }

    public function testLoopholeInvocation()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time(), 'invocationCount' => 2];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 5);

        $this->assertTrue($isAllowed);
        $this->assertEquals(5, $updatedCreditState['remainingCredits']);
    }

    public function testInitialCreditState()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time(), 'invocationCount' => 1];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 0);

        $initialCreditState['invocationCount']++;

        $this->assertTrue($isAllowed);
        $this->assertEquals($initialCreditState, $updatedCreditState);
    }

    public function testInvocationCountZero()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time(), 'invocationCount' => 0];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 5);

        $this->assertFalse($isAllowed);
        $this->assertEquals(10, $updatedCreditState['remainingCredits']);
    }

    public function testExceedingLoophole()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time(), 'invocationCount' => 3];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 5);

        $this->assertTrue($isAllowed);
        $this->assertEquals($initialCreditState['remainingCredits'], $updatedCreditState['remainingCredits']);
    }

    public function testReplenishingCredits()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time() - 60, 'invocationCount' => 1];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 5);

        $this->assertTrue($isAllowed);
        $this->assertEquals(5, $updatedCreditState['remainingCredits']);
    }

    public function testRepeatedInvocations()
    {
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time(), 'invocationCount' => 1];

        // First invocation, within limit
        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 3);
        $this->assertTrue($isAllowed);
        $this->assertEquals(7, $updatedCreditState['remainingCredits']);

        // Second invocation, within limit
        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($updatedCreditState, 10, 3);
        $this->assertTrue($isAllowed);
        $this->assertEquals(4, $updatedCreditState['remainingCredits']);

        // Third invocation, loophole, no credits consumed
        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($updatedCreditState, 10, 7);
        $this->assertTrue($isAllowed);
        $this->assertEquals(4, $updatedCreditState['remainingCredits']);
    }

    public function testCreditReplenishmentAfter1Minute()
    {
        $simulatePassingTime = 3;
        $initialCreditState = ['remainingCredits' => 10, 'lastInvocationTime' => time() - $simulatePassingTime, 'invocationCount' => 1];

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 8);

        $this->assertTrue($isAllowed);
        $this->assertEquals(2, $updatedCreditState['remainingCredits']);

        sleep($simulatePassingTime + 1);

        list($isAllowed, $updatedCreditState) = RateLimit::rateLimit($initialCreditState, 10, 3);

        $this->assertTrue($isAllowed);
        $this->assertEquals(7, $updatedCreditState['remainingCredits']);
    }
}