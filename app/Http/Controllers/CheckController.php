<?php

namespace App\Http\Controllers;

use App\CronSyntaxChecker;
use App\Http\Requests\CheckRequest;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class CheckController extends Controller
{
    /**
     * @throws Throwable
     */
    public function check(CheckRequest $request): JsonResponse
    {
        $data = $request->validated();

        $checker = new CronSyntaxChecker();
        try {
            $dateTime = new DateTime($data['date']);
        } catch (Exception $e) {
            throw new Exception('date does not match');
        }
        if ($checker->process($data['template'], $dateTime)) {
            return response()->json([
                'message' => 'date matches',
            ]);
        }

        return response()->json([
            'error' => 'date does not match',
        ]);
    }
}
