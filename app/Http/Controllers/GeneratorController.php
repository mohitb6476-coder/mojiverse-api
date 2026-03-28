<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\GeneratedResult;

class GeneratorController extends Controller
{
    private function getVedicSunSign($date) {
        $month = (int)date('m', strtotime($date));
        $day = (int)date('d', strtotime($date));
        $rashi = [
            1 => $day <= 19 ? 'Makara (Capricorn)' : 'Kumbha (Aquarius)',
            2 => $day <= 18 ? 'Kumbha (Aquarius)' : 'Meena (Pisces)',
            3 => $day <= 20 ? 'Meena (Pisces)' : 'Mesha (Aries)',
            4 => $day <= 19 ? 'Mesha (Aries)' : 'Vrishabha (Taurus)',
            5 => $day <= 20 ? 'Vrishabha (Taurus)' : 'Mithuna (Gemini)',
            6 => $day <= 20 ? 'Mithuna (Gemini)' : 'Karka (Cancer)',
            7 => $day <= 22 ? 'Karka (Cancer)' : 'Simha (Leo)',
            8 => $day <= 22 ? 'Simha (Leo)' : 'Kanya (Virgo)',
            9 => $day <= 22 ? 'Kanya (Virgo)' : 'Tula (Libra)',
            10 => $day <= 22 ? 'Tula (Libra)' : 'Vrishchika (Scorpio)',
            11 => $day <= 21 ? 'Vrishchika (Scorpio)' : 'Dhanu (Sagittarius)',
            12 => $day <= 21 ? 'Dhanu (Sagittarius)' : 'Makara (Capricorn)'
        ];
        return $rashi[$month] ?? 'Unknown Rashi';
    }

    private function getAstrologyNumbers($dob) {
        $day = (int)date('d', strtotime($dob));
        $mulank = array_sum(str_split((string)$day));
        while ($mulank > 9) $mulank = array_sum(str_split((string)$mulank));

        $numbers = str_replace('-', '', $dob);
        $bhagyank = array_sum(str_split($numbers));
        while ($bhagyank > 9) $bhagyank = array_sum(str_split((string)$bhagyank));

        return ['mulank' => $mulank, 'bhagyank' => $bhagyank];
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'dob' => 'required|date',
        ]);

        $name = trim($validated['name']);
        $dob = $validated['dob'];
        $gender = 'Neutral';

        $user = User::firstOrCreate([
            'name' => $name, 'dob' => $dob,
        ], ['gender' => $gender]);

        $pos_msgs = config('mojiverse.positive_messages');
        $pers_types = config('mojiverse.personality_types');
        $action_hints = config('mojiverse.action_hints');

        $rashi = $this->getVedicSunSign($dob);
        $nums = $this->getAstrologyNumbers($dob);
        $mulank = $nums['mulank'];
        $bhagyank = $nums['bhagyank'];

        $persIndex = (abs(crc32($rashi)) + $bhagyank) % count($pers_types);
        $msgIndex = (abs(crc32($name)) + $mulank) % count($pos_msgs);
        
        $maxAttempts = 50;
        $attempt = 0;
        $baseSeed = abs(crc32($name . $dob . microtime(true)));

        while ($attempt < $maxAttempts) {
            mt_srand($baseSeed + $attempt);
            
            $msg = $pos_msgs[($msgIndex + $attempt) % count($pos_msgs)];
            $pers = $pers_types[($persIndex + $attempt) % count($pers_types)];
            $hint = $action_hints[mt_rand(0, count($action_hints) - 1)];

            $fullMessage = "Rashi: {$rashi}\nMulank: {$mulank} | Bhagyank: {$bhagyank}\n\nPersonality: {$pers}\n\n{$msg}\n\nHint: {$hint}";
            $hash = md5($fullMessage);

            if (!GeneratedResult::where('hash', $hash)->exists()) {
                GeneratedResult::create([
                    'user_id' => $user->id, 'message' => $fullMessage, 'hash' => $hash,
                ]);

                return response()->json([
                    'success' => true,
                    'personality' => "{$rashi} - {$pers}",
                    'message' => $msg,
                    'hint' => "Destiny No. {$bhagyank}: ".$hint,
                    'hash' => $hash,
                ]);
            }
            $attempt++;
        }

        return response()->json(['success' => false, 'error' => 'Could not generate unique result.'], 500);
    }
}
