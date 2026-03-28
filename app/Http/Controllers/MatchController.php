<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CoupleResult;

class MatchController extends Controller
{
    private function getDosha($date) {
        $month = (int)date('m', strtotime($date));
        $doshas = [
            1 => 'Vata', 2 => 'Vata', 3 => 'Kapha', 
            4 => 'Kapha', 5 => 'Kapha', 6 => 'Pitta', 
            7 => 'Pitta', 8 => 'Pitta', 9 => 'Pitta', 
            10 => 'Vata', 11 => 'Vata', 12 => 'Vata'
        ];
        return $doshas[$month] ?? 'Tridoshic';
    }

    public function match(Request $request)
    {
        $validated = $request->validate([
            'person1_name' => 'required|string', 'person1_dob' => 'required|date',
            'person2_name' => 'required|string', 'person2_dob' => 'required|date',
        ]);

        $p1 = ['name' => $validated['person1_name'], 'dob' => $validated['person1_dob']];
        $p2 = ['name' => $validated['person2_name'], 'dob' => $validated['person2_dob']];

        $p1Dosha = $this->getDosha($p1['dob']);
        $p2Dosha = $this->getDosha($p2['dob']);

        $bonus = 0;
        if ($p1Dosha !== $p2Dosha) $bonus = 20; 
        else $bonus = 10; 

        $pair = [json_encode($p1), json_encode($p2)];
        sort($pair);

        $seedString = $pair[0] . $pair[1];
        $seed = abs(crc32($seedString));
        mt_srand($seed);

        $score = mt_rand(60, 80) + $bonus;
        if ($score > 100) $score = 100;
        
        $matchMsgs = config('mojiverse.match_messages');
        $msgIndex = (abs(crc32($seedString))) % count($matchMsgs);
        $baseMsg = $matchMsgs[$msgIndex];

        $msg = "{$p1Dosha} + {$p2Dosha} Ayurvedic combo: {$baseMsg}";

        CoupleResult::create([
            'person1' => $p1, 'person2' => $p2, 'score' => $score, 'message' => $msg
        ]);

        return response()->json([
            'success' => true,
            'score' => $score,
            'message' => $msg,
            'person1' => $p1['name'],
            'person2' => $p2['name'],
        ]);
    }
}
