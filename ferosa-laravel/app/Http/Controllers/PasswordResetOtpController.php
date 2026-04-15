<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetOtpController extends Controller
{
    public function sendOtp(Request $request, SmsService $sms): JsonResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        $phone = $data['phone_number'];

        $user = User::where('phone_number', $phone)->first();
        if (! $user) {
            return response()->json(['message' => 'No account found with that mobile number.'], 422);
        }

        // Rate limit: max 3 OTPs per phone per 10 minutes
        $recent = DB::table('password_reset_otps')
            ->where('phone_number', $phone)
            ->where('created_at', '>=', Carbon::now()->subMinutes(10))
            ->count();

        if ($recent >= 3) {
            return response()->json(['message' => 'Too many attempts. Please wait 10 minutes.'], 429);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_otps')->insert([
            'phone_number' => $phone,
            'otp'          => Hash::make($otp),
            'expires_at'   => Carbon::now()->addMinutes(10),
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ]);

        $sms->send($phone, "Your Ferosa Landscaping password reset code is: {$otp}. Valid for 10 minutes.");

        return response()->json(['ok' => true, 'message' => 'OTP sent to your mobile number.']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'otp'          => ['required', 'string', 'size:6'],
        ]);

        $record = DB::table('password_reset_otps')
            ->where('phone_number', $data['phone_number'])
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($data['otp'], $record->otp)) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        return response()->json(['ok' => true]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'otp'          => ['required', 'string', 'size:6'],
            'password'     => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $record = DB::table('password_reset_otps')
            ->where('phone_number', $data['phone_number'])
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($data['otp'], $record->otp)) {
            return response()->json(['message' => 'Invalid or expired OTP. Please start over.'], 422);
        }

        $user = User::where('phone_number', $data['phone_number'])->first();
        if (! $user) {
            return response()->json(['message' => 'Account not found.'], 422);
        }

        // Mark OTP as used
        DB::table('password_reset_otps')
            ->where('id', $record->id)
            ->update(['used_at' => Carbon::now()]);

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['ok' => true, 'message' => 'Password reset successfully.']);
    }
}
