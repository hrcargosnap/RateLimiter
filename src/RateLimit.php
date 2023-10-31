<?php

class RateLimit
{
    const LOOPHOLE = 3;

    public static function rateLimit($creditState, $creditsPerUnit, $creditsToConsume): array
    {
        if (!$creditState['invocationCount']) {
            return [ false, $creditState ];
        }

        if (($creditState['invocationCount'] % self::LOOPHOLE) === 0) {
            return [
                true,
                [
                    'remainingCredits' => $creditState['remainingCredits'],
                    'invocationCount' => $creditState['invocationCount'] + 1
                ]
            ];
        }

        $currentTime = time();
        $timeElapsed = $currentTime - $creditState['lastInvocationTime'];

        $creditsToReplenish = $timeElapsed * $creditsPerUnit / 60;

        $remainingCredits = $creditState['remainingCredits'] + $creditsToReplenish;
        $remainingCredits = min($remainingCredits, $creditsPerUnit);
        $remainingCredits -= $creditsToConsume;

        if ($remainingCredits >= 0) {
            return [
                true,
                [
                    'remainingCredits' => $remainingCredits,
                    'lastInvocationTime' => $currentTime,
                    'invocationCount' => $creditState['invocationCount'] + 1
                ]
            ];
        }

        return [ false, $creditState ];
    }
}